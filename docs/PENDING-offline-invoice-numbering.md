# Pending: offline invoice number clash (handoff note)

Written 2026-10-12 so work can continue from another device / Claude account.
Production SQL in this note is meant to be run by a person on the production DB
(local dev DB is separate and does not contain this data).

**Status 2026-10-16: PENDING 1 is still open. PENDING 2 is rebuilt as per-driver number
ranges inside "A"** (A10001, A20001 ...), which the unchanged mobile app accepts; the earlier
letter series is gone. Needs `php artisan migrate` on deploy (see that section).
Related changes since: 41a263e added a content duplicate guard
(same driver + customer + identical lines returns the saved invoice), and it was then
narrowed from "same day" to "saved within the last 10 minutes" so genuine repeat
orders are no longer swallowed. Note for PENDING 1: if ZAHID's phone syncs the invoice
now, it is attached to his *current* trip and deducts stock from his current lorry
(it keeps its 2026-10-12 date) - check the uuid query first.

## What happened

- Receipt printed by the driver app: **`OC2610/A00004`**, driver **ZAHID ALAM**
  (drivers.id = 1), customer **VISTA ACE BISTRO** (customers.id = 357),
  date `2026-10-12 15:12:11`, product id 11, 20 x RM 2.50 = **RM 50.00**, payment
  Credit (type 2), uuid `90684405-9216-4444-a406-8782dd52cd07`.
- Another driver, **IBRAHIM MIAH** (drivers.id = 4), was issued the **same** offline
  number and synced first: `POST /api/v1/driver/invoice` at 14:37:24 -> 200, saved
  as invoices.id 5558 (customer 190, RM 23.00, Cash). api_logs id 114619.
- ZAHID's phone created its invoice offline at 15:12:11 with a stale counter, then
  sent `POST /api/v1/driver/invoice` at 16:09:21 (api_logs 115136) and 16:31:42
  (api_logs 115163). Both returned **400 `invoice_no_already_exists`**.
- Result: the invoice was never saved. No invoice row, no stock deduction, no credit.
  The customer still holds a paper receipt with number `OC2610/A00004`.

Root cause: `GET /api/v1/driver/offline-data` (`DriverController::offlineData`) returns
"highest existing `/A` number + 1" and reserves nothing, so two phones that fetch it
before either syncs print the same offline numbers.

## Already done (commit 376e6f3, pushed to `main`)

`DriverController::addinvoice()` (single endpoint) no longer rejects a clashing number:

- number held by a *different* invoice -> saved under the next free online number
  (`Invoice::generateInvoiceNo`), response data carries `renumbered_from` = the number
  the phone sent;
- same driver + same customer + same amount re-sending a saved number (older app
  versions send no uuid) -> returns the saved invoice, creates nothing;
- replacing a status-0 invoice (`invoice_id` supplied) whose number is taken by another
  invoice -> still 400 (inconsistent data).

`bulkCreateInvoice()` already renumbered on a clash before this. **This fix must be
deployed to production before anything below can work.**

Known limitation: the saved number now differs from the printed receipt, and the
printed number belongs to a different invoice. The server cannot change a printed receipt.

## PENDING 1 - recreate the missing invoice for VISTA ACE BISTRO

**Decided 2026-10-15 (supersedes the options below):** create it directly in the DB as
`OC2610/A00004-1`, dated 2026-10-12, customer 357, driver 1, Credit, 20 x RM 2.50 on
product 11, `trip_id = 511`, remark `System issue duplicated invoice number - reassigned`,
reusing uuid `90684405-9216-4444-a406-8782dd52cd07` so a later retry from ZAHID's phone
returns this invoice instead of creating another. **No stock deduction** (user's call).
The SQL (check, insert invoice, insert line, verify) was given to the user to run on
production; confirm with the verify query whether it has been run. Receipt to be reprinted
with the new number.

- **Number to use:** the next number from the normal online series (`OC2610/00xxx`),
  assigned by the server. Do not reuse `A00004` (taken). Do not hand-pick a new `/A`
  number: phones derive their next offline number from the highest saved `/A`, so a
  manually inserted one can cause a new clash.
- **Preferred:** after deploy, ZAHID's phone retries the same request (same uuid) and it
  saves with the original date, his trip, stock deducted, correct number. He must sync
  **before ending his trip** (after trip end `/driver/invoice` returns 401
  "trip had not started" and the invoice stays stuck).
- **Fallback:** replay the logged request body (api_logs 115163) to `POST /driver/invoice`
  with ZAHID's `session` header: customer_id 357, type 2, date `2026-10-12 15:12:11`,
  invoicedetail [{product_id 11, quantity 20, price 2.5, foc 0}], invoiceno `null`.
- **Do not use the admin "Create Invoice" form** (`InvoiceController::store`): it does not
  deduct lorry stock, does not set trip_id (so it is missing from trip/lorry reports) and
  creates a pending driver task.
- Customer's receipt must be reprinted with the final number once saved.

Verify:

```sql
SELECT id, invoiceno, date, customer_id, trip_id, paymentterm, status, autocount_status
FROM invoices
WHERE uuid = '90684405-9216-4444-a406-8782dd52cd07';
```

## PENDING 2 - unique offline numbers per driver (per-driver NUMBER RANGES, built 2026-10-16)

History: a per-driver letter series (`OC2610/B00001`, commit dc1253a) broke the mobile app
and was switched off in 89eecb9. It is now replaced by number ranges inside "A", which
works with the unchanged app.

**Why letters broke the app (verified in app source):** GitLab waikit.dev/van_sales, tag
`android|ios/iceProd/1.0.11_26080701` (commit 11069d9), `lib/domain/service/impl/delivery_impl.dart`.
At Start Trip, *before* the start-trip API, the app calls `offline-data` and does
`int.parse(runningNo.split("/").last.replaceAll("A", ""))` for `invoice.offline` and
`delivery_order.offline`. Each offline sale then prints `"OC"`/`"DOOC"` + phone yyMM + `"/A"` +
`runNo.padLeft(5, "0")` and adds 1. So the app only takes the digits; "OC", yyMM and "/A"
are hard-coded. Any other letter makes `int.parse` throw and the trip never starts.

**Scheme:** offline number = `A` + (range x 10000 + seq), seq 1..9999 per driver per month,
invoices and DOs counted separately (`Driver::OFFLINE_RANGE_SIZE`, `offlineNumberBounds()`).

| Driver range | Invoice numbers | DO numbers |
|---|---|---|
| 1 | `OC2610/A10001`, `A10002` ... | `DOOC2610/A10001` ... |
| 2 | `OC2610/A20001` ... | `DOOC2610/A20001` ... |
| 12 | `OC2610/A120001` ... | `DOOC2610/A120001` ... |
| none (legacy) | `OC2610/A00001`-`A09999` (old shared block) | same |

- Migration `2026_10_16_000001_replace_offline_series_with_offline_range_on_drivers_table`
  adds `drivers.offline_range` (unique per company), backfills it (a driver who had a letter
  keeps its position: B = 1, C = 2, ...; others take the lowest free range in id order) and
  drops `offline_series`. **Run `php artisan migrate` on production when deploying.**
- `offlineData()` passes `$driver->offline_range` to `Invoice::generateOfflineInvoiceNo()` /
  `DeliveryOrder::generateOfflineDoNo()`. Next number = highest saved number inside that
  driver's block this month + 1 (first = range x 10000 + 1). Legacy only looks at 1-9999, so
  it never picks up A10001. Passing 9999 in a month throws a clear error (this blocks that
  driver's Start Trip, since the app calls offline-data first). Online numbering is untouched.
- Admin: "Offline Number Range" field on the driver form (number, 1-65535, unique per
  company; blank on create = lowest free range, blank on edit = keep current), plus
  "Offline Number Range" and "Prints As" (e.g. `A20001, A20002 ...`) on the driver list and
  view page.
- Residual risk the server can't remove: if a driver starts a new trip before their offline
  sales have synced, the server hands the same block start again, so that driver's phone
  can repeat its own numbers (never another driver's). Changing a driver's range takes
  effect at their next Start Trip, so don't hand their old range to someone else mid-trip.
  The renumber-on-clash safety net still applies.
- Before deploying, check this month's legacy numbers are below 10000 (should be tiny):

```sql
SELECT MAX(CAST(SUBSTRING_INDEX(invoiceno,'/A',-1) AS UNSIGNED)) FROM invoices WHERE invoiceno LIKE 'OC2610/A%';
SELECT MAX(CAST(SUBSTRING_INDEX(invoiceno,'/A',-1) AS UNSIGNED)) FROM deliveryorders WHERE invoiceno LIKE 'DOOC2610/A%';
```

- After migrating, see each driver's range:

```sql
SELECT company_id, id, name, offline_range FROM drivers ORDER BY company_id, id;
```

- Test on one phone after deploy: Start Trip works, an offline sale prints
  `OC2610/A{range}0001`, the next one +1.

- Check how the app uses the number (production SQL):

```sql
SELECT d.name AS driver, DATE(i.created_at) AS day,
       GROUP_CONCAT(i.invoiceno ORDER BY i.id SEPARATOR ', ') AS offline_numbers
FROM invoices i JOIN drivers d ON d.id = i.driver_id
WHERE i.invoiceno REGEXP '^(OC|OS)[0-9]{4}/A[0-9]+' AND i.created_at >= '2026-10-01'
GROUP BY d.name, DATE(i.created_at) ORDER BY day, driver;
```

Optional extra (not built): store the originally printed number in a new column so staff
can search by what is on the receipt.

## Useful api_logs searches (production)

The request body is stored double-encoded: `invoiceno` appears as
`\"invoiceno\":\"OC2610\\\/A00004\"`. Strip backslashes first:

```sql
SELECT l.id, l.created_at, d.name AS driver, l.url, l.status_code, LEFT(l.response_body, 300) AS response_body
FROM api_logs l LEFT JOIN drivers d ON d.id = l.driver_id
WHERE REPLACE(l.request_body, '\\', '') LIKE '%invoiceno":"OC2610/A00004"%'
ORDER BY l.id;
```

All clashes rejected before the fix went live:

```sql
SELECT l.id, l.created_at, d.name AS driver, LEFT(l.request_body, 500) AS request_body
FROM api_logs l LEFT JOIN drivers d ON d.id = l.driver_id
WHERE l.response_body LIKE '%invoice_no_already_exists%' AND l.created_at >= '2026-10-01'
ORDER BY l.id;
```

Every row there is a receipt a customer holds with no invoice on the server; the full
items and customer are in `request_body`.
