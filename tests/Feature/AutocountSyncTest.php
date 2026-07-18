<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

/**
 * Covers the web -> AutoCount queue flow and the plugin-facing API endpoints.
 *
 * The project has no create_users_table migration (dev uses an existing MySQL DB),
 * so we build just the tables these features touch instead of RefreshDatabase.
 */
class AutocountSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoiceno')->nullable();
            $table->date('date')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('paymentterm')->nullable();
            $table->integer('status')->default(0);
            $table->tinyInteger('autocount_status')->default(0);
            $table->string('autocount_docno')->nullable();
            $table->text('autocount_error')->nullable();
            $table->timestamp('autocount_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('invoice_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->float('price', 10, 2);
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('company')->nullable();
            $table->string('paymentterm')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('postcode')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->float('price', 10, 2)->nullable();
            $table->string('classification_code')->nullable();
        });

        // Each company is a branch, mapped to one AutoCount account book via its code.
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('name')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('companies');
        parent::tearDown();
    }

    private function makeCompany(int $id, string $code): void
    {
        DB::table('companies')->insert(['id' => $id, 'code' => $code, 'name' => $code . ' Branch']);
    }

    private function makeInvoice(array $overrides = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'invoiceno'        => 'OS2607/00001',
            'date'             => '2026-07-01',
            'customer_id'      => 1,
            'status'           => 0,
            'autocount_status' => Invoice::AUTOCOUNT_NOT_SYNCED,
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $overrides));
    }

    /** @test */
    public function queue_autocount_marks_selected_invoices_as_queued()
    {
        $a = $this->makeInvoice(['invoiceno' => 'A']);
        $b = $this->makeInvoice(['invoiceno' => 'B']);

        $response = $this->withoutMiddleware()
            ->postJson('/invoices/queue-autocount', ['ids' => [$a, $b]]);

        $response->assertStatus(200)->assertJson(['count' => 2]);

        $this->assertEquals(Invoice::AUTOCOUNT_QUEUED, DB::table('invoices')->find($a)->autocount_status);
        $this->assertEquals(Invoice::AUTOCOUNT_QUEUED, DB::table('invoices')->find($b)->autocount_status);
    }

    /** @test */
    public function queue_autocount_allows_resyncing_an_already_synced_invoice()
    {
        // A synced invoice can be re-queued (e.g. it was deleted/edited in AutoCount and
        // needs to be pushed again). Re-queueing resets it to QUEUED and clears stale
        // sync metadata so the plugin picks it up on the next tick.
        $synced = $this->makeInvoice([
            'autocount_status'    => Invoice::AUTOCOUNT_SYNCED,
            'autocount_docno'     => 'OS2607/00001',
            'autocount_error'     => null,
            'autocount_synced_at' => now(),
        ]);

        $response = $this->withoutMiddleware()
            ->postJson('/invoices/queue-autocount', ['ids' => [$synced]]);

        $response->assertStatus(200)->assertJson(['count' => 1]);

        $row = DB::table('invoices')->find($synced);
        $this->assertEquals(Invoice::AUTOCOUNT_QUEUED, $row->autocount_status);
        $this->assertNull($row->autocount_docno);
        $this->assertNull($row->autocount_synced_at);
    }

    /** @test */
    public function queue_autocount_requires_at_least_one_id()
    {
        $this->withoutMiddleware()
            ->postJson('/invoices/queue-autocount', ['ids' => []])
            ->assertStatus(422);
    }

    /** @test */
    public function queued_endpoint_returns_queued_invoices_with_details()
    {
        $this->makeCompany(1, 'OC');
        DB::table('customers')->insert(['id' => 1, 'code' => '300-A001', 'company' => 'ABC Sdn Bhd', 'paymentterm' => '1']);
        DB::table('products')->insert(['id' => 1, 'code' => 'P001', 'name' => 'Widget', 'price' => 12.50, 'classification_code' => '022']);

        $queued = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'paymentterm' => '2', 'company_id' => 1]);
        $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_NOT_SYNCED, 'company_id' => 1]); // must be excluded

        DB::table('invoice_details')->insert([
            'invoice_id' => $queued, 'product_id' => 1, 'quantity' => 3, 'price' => 10.50,
            'remark' => 'Line remark', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/autocount/invoices/queued?book=OC');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $queued)
            ->assertJsonPath('0.paymentterm', '2')          // invoice's own term wins
            ->assertJsonPath('0.customer.code', '300-A001')
            ->assertJsonPath('0.details.0.item_code', 'P001')
            ->assertJsonPath('0.details.0.quantity', 3)
            // Product-master fields for creating the item in AutoCount from web data.
            ->assertJsonPath('0.details.0.item_name', 'Widget')
            ->assertJsonPath('0.details.0.item_price', 12.5)
            ->assertJsonPath('0.details.0.classification_code', '022');
    }

    /** @test */
    public function queued_endpoint_falls_back_to_customer_payment_term()
    {
        $this->makeCompany(1, 'OC');
        DB::table('customers')->insert(['id' => 1, 'code' => '300-A001', 'company' => 'ABC Sdn Bhd', 'paymentterm' => '2']);

        // Invoice has no payment term of its own; the customer's should be used.
        $queued = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'paymentterm' => null, 'company_id' => 1]);

        $this->getJson('/api/autocount/invoices/queued?book=OC')
            ->assertStatus(200)
            ->assertJsonPath('0.id', $queued)
            ->assertJsonPath('0.paymentterm', '2');
    }

    /** @test */
    public function queued_endpoint_only_returns_invoices_for_the_connected_account_book()
    {
        $this->makeCompany(1, 'OC');
        $this->makeCompany(2, 'OS');

        // One queued invoice per branch; only the OC branch's should come back.
        $oc = $this->makeInvoice(['invoiceno' => 'OC-1', 'autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'company_id' => 1]);
        $this->makeInvoice(['invoiceno' => 'OS-1', 'autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'company_id' => 2]);

        $this->getJson('/api/autocount/invoices/queued?book=OC')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $oc)
            ->assertJsonPath('0.company_id', 1);
    }

    /** @test */
    public function queued_endpoint_returns_empty_when_book_matches_no_branch()
    {
        $this->makeCompany(1, 'OC');
        $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'company_id' => 1]);

        $this->getJson('/api/autocount/invoices/queued?book=UNKNOWN')
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    /** @test */
    public function queued_endpoint_returns_empty_when_book_is_missing()
    {
        $this->makeCompany(1, 'OC');
        $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED, 'company_id' => 1]);

        // No book param -> we cannot tell which account book is connected, so sync nothing.
        $this->getJson('/api/autocount/invoices/queued')
            ->assertStatus(200)
            ->assertExactJson([]);
    }

    /** @test */
    public function companies_endpoint_lists_selectable_companies()
    {
        // The plugin fetches this list so the user can toggle which account book
        // (company) to sync, instead of hardcoding COMPANY_CODE in .env.
        $this->makeCompany(2, 'OS');
        $this->makeCompany(1, 'OC');

        $this->getJson('/api/autocount/companies')
            ->assertStatus(200)
            ->assertJsonCount(2)
            // Ordered by code for a stable toggle list.
            ->assertJsonPath('0.code', 'OC')
            ->assertJsonPath('0.name', 'OC Branch')
            ->assertJsonPath('1.code', 'OS');
    }

    /** @test */
    public function companies_endpoint_skips_companies_without_a_code()
    {
        $this->makeCompany(1, 'OC');
        // A branch with no code cannot map to an account book, so it is not selectable.
        DB::table('companies')->insert(['id' => 2, 'code' => null, 'name' => 'Draft Branch']);

        $this->getJson('/api/autocount/companies')
            ->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.code', 'OC');
    }

    /** @test */
    public function synced_endpoint_marks_invoice_synced_with_docno()
    {
        $id = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED]);

        $this->postJson("/api/autocount/invoices/{$id}/synced", ['docno' => 'INV-0000123'])
            ->assertStatus(200);

        $row = DB::table('invoices')->find($id);
        $this->assertEquals(Invoice::AUTOCOUNT_SYNCED, $row->autocount_status);
        $this->assertEquals('INV-0000123', $row->autocount_docno);
        $this->assertNotNull($row->autocount_synced_at);
    }

    /** @test */
    public function failed_endpoint_marks_invoice_failed_with_error()
    {
        $id = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED]);

        $this->postJson("/api/autocount/invoices/{$id}/failed", ['error' => 'Debtor not found'])
            ->assertStatus(200);

        $row = DB::table('invoices')->find($id);
        $this->assertEquals(Invoice::AUTOCOUNT_FAILED, $row->autocount_status);
        $this->assertEquals('Debtor not found', $row->autocount_error);
    }
}
