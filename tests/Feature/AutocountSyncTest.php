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
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        parent::tearDown();
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
    public function queue_autocount_skips_already_synced_invoices()
    {
        $synced = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_SYNCED]);

        $response = $this->withoutMiddleware()
            ->postJson('/invoices/queue-autocount', ['ids' => [$synced]]);

        $response->assertStatus(200)->assertJson(['count' => 0]);
        $this->assertEquals(Invoice::AUTOCOUNT_SYNCED, DB::table('invoices')->find($synced)->autocount_status);
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
        DB::table('customers')->insert(['id' => 1, 'code' => '300-A001', 'company' => 'ABC Sdn Bhd']);
        DB::table('products')->insert(['id' => 1, 'code' => 'P001', 'name' => 'Widget']);

        $queued = $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_QUEUED]);
        $this->makeInvoice(['autocount_status' => Invoice::AUTOCOUNT_NOT_SYNCED]); // must be excluded

        DB::table('invoice_details')->insert([
            'invoice_id' => $queued, 'product_id' => 1, 'quantity' => 3, 'price' => 10.50,
            'remark' => 'Line remark', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/autocount/invoices/queued');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $queued)
            ->assertJsonPath('0.customer.code', '300-A001')
            ->assertJsonPath('0.details.0.item_code', 'P001')
            ->assertJsonPath('0.details.0.quantity', 3);
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
