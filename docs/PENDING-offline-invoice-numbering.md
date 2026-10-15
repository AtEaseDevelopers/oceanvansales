# Pending: offline invoice number clash (handoff note)

Written 2026-10-12 so work can continue from another device / Claude account.
Production SQL in this note is meant to be run by a person on the production DB
(local dev DB is separate and does not contain this data).

**Status 2026-10-15: PENDING 1 is still open. PENDING 2 is built** (per-driver offline
series letters, see that section) and needs deploying with `php artisan migrate`.
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

## PENDING 2 - unique offline numbers per driver (BUILT 2026-10-15)

Each driver has their own offline series letter(s) in `drivers.offline_series`, so
offline numbers look like `OC2610/B00001` (driver with series B), `OC2610/C00001`, ...
Same shape as the old `OC2610/A00001`, only the letter differs. DOs: `DOOC2610/B00001`.

- Migration `2026_10_15_000001_add_offline_series_to_drivers_table` adds the column
  (unique per company) and backfills every existing driver with B, C, D, ... per company
  in driver id order. **Run `php artisan migrate` on production when deploying.**
- "A" stays reserved for the old shared series (validation blocks it). Codes run B..Z,
  then BA..ZZ.
- Admin: driver create/edit form has an "Offline Invoice Series" field (also on the
  driver view page and as the last column of the driver list). Blank on create =
  auto-assign the next free code; blank on edit = keep the current one. Validation:
  1-3 letters, upper-cased automatically, unique within the company.
- `offlineData()` passes the driver's series to `Invoice::generateOfflineInvoiceNo()` /
  `DeliveryOrder::generateOfflineDoNo()`; matching uses REGEXP so series B does not pick
  up another driver's BA numbers. Online numbering is unaffected (letter-led numbers cast
  to 0 there). The renumber-on-clash safety net stays.
- Still to verify on one phone after deploy: the app must use the string from
  `offline-data` and count up the trailing digits. Expect `.../B00001` then `.../B00002`
  for the driver with series B. Phones keep an old cached `A` number until their next
  `offline-data` call, so a clash is still possible briefly after deploy.
- See which letter each production driver got after migrating:

```sql
SELECT company_id, id, name, offline_series FROM drivers ORDER BY company_id, id;
```

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
