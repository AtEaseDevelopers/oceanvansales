<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

/**
 * Covers the "Merge to Invoice" action on the Delivery Orders list: it appends the selected
 * DOs' lines into an EXISTING invoice the user picks (not a new one) and resets that invoice
 * to NOT synced to AutoCount, since its contents changed and any previous push is now stale.
 *
 * The project has no create_users_table migration (dev uses an existing MySQL DB),
 * so we build just the tables this feature touches instead of RefreshDatabase.
 */
class DeliveryOrderMergeConvertTest extends TestCase
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
            $table->text('remark')->nullable();
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
            $table->integer('deliveryorder_id')->nullable();
            $table->integer('quantity');
            $table->float('price', 10, 2);
            $table->float('totalprice', 10, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveryorders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoiceno')->nullable();
            $table->date('date')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->integer('invoice_id')->nullable();
            $table->timestamps();
        });

        Schema::create('deliveryorder_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('deliveryorder_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->float('price', 10, 2);
            $table->float('totalprice', 10, 2)->nullable();
            $table->string('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('company')->nullable();
            $table->integer('company_id')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('deliveryorder_details');
        Schema::dropIfExists('deliveryorders');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        parent::tearDown();
    }

    private function makeInvoice(array $overrides = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'invoiceno'        => 'OX2608/00001',
            'date'             => '2026-08-01',
            'customer_id'      => 1,
            // "Merge to Invoice" only targets combined invoices, so the default target is one.
            'remark'           => 'Combined from 2 delivery order(s)',
            'status'           => 1,
            'autocount_status' => Invoice::AUTOCOUNT_SYNCED,
            'autocount_docno'  => 'INV-999',
            'autocount_synced_at' => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ], $overrides));
    }

    /** Insert a DO with one line and return its id. */
    private function makeDo(array $overrides = [], array $line = []): int
    {
        $id = DB::table('deliveryorders')->insertGetId(array_merge([
            'invoiceno' => 'DOOX2608/00001', 'date' => '2026-08-01', 'customer_id' => 99, 'invoice_id' => null,
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides));

        DB::table('deliveryorder_details')->insert(array_merge([
            'deliveryorder_id' => $id, 'product_id' => 1, 'quantity' => 2, 'price' => 3.50,
            'totalprice' => 7.00, 'remark' => null, 'created_at' => now(), 'updated_at' => now(),
        ], $line));

        return $id;
    }

    /** @test */
    public function it_merges_selected_dos_into_an_existing_invoice_and_resets_it_to_not_synced()
    {
        $invoiceId = $this->makeInvoice();
        // The target invoice already has one line of its own.
        DB::table('invoice_details')->insert([
            'invoice_id' => $invoiceId, 'product_id' => 9, 'quantity' => 1, 'price' => 2.00,
            'totalprice' => 2.00, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $do1 = $this->makeDo([], ['product_id' => 1, 'quantity' => 2, 'price' => 3.50, 'totalprice' => 7.00]);
        $do2 = $this->makeDo([], ['product_id' => 2, 'quantity' => 5, 'price' => 1.00, 'totalprice' => 5.00]);

        $response = $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [$do1, $do2], 'invoice_id' => $invoiceId]);

        $response->assertStatus(200)->assertJson(['count' => 2]);

        // No new invoice is created — the DOs flow into the chosen one.
        $this->assertEquals(1, DB::table('invoices')->count());

        // The target invoice is reset to NOT synced and its stale sync metadata is cleared.
        $invoice = DB::table('invoices')->find($invoiceId);
        $this->assertEquals(Invoice::AUTOCOUNT_NOT_SYNCED, $invoice->autocount_status);
        $this->assertNull($invoice->autocount_docno);
        $this->assertNull($invoice->autocount_synced_at);

        // Its original line plus both DOs' lines are now on the invoice; DOs back-link to it.
        $this->assertEquals(3, DB::table('invoice_details')->where('invoice_id', $invoiceId)->count());
        $this->assertEquals($invoiceId, DB::table('deliveryorders')->find($do1)->invoice_id);
        $this->assertEquals($invoiceId, DB::table('deliveryorders')->find($do2)->invoice_id);
    }

    /** @test */
    public function it_skips_already_converted_dos()
    {
        $invoiceId = $this->makeInvoice();
        $fresh = $this->makeDo();
        $converted = $this->makeDo(['invoice_id' => 555]);

        $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [$fresh, $converted], 'invoice_id' => $invoiceId])
            ->assertStatus(200)
            ->assertJson(['count' => 1]);

        // Only the fresh DO's single line is appended; the converted DO is untouched.
        $this->assertEquals(1, DB::table('invoice_details')->where('invoice_id', $invoiceId)->count());
        $this->assertEquals(555, DB::table('deliveryorders')->find($converted)->invoice_id);
    }

    /** @test */
    public function it_requires_at_least_one_id()
    {
        $invoiceId = $this->makeInvoice();

        $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [], 'invoice_id' => $invoiceId])
            ->assertStatus(422);
    }

    /** @test */
    public function it_requires_an_invoice_to_merge_into()
    {
        $do = $this->makeDo();

        $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [$do]])
            ->assertStatus(422);
    }

    /** @test */
    public function it_fails_when_the_target_invoice_is_not_found()
    {
        $do = $this->makeDo();

        $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [$do], 'invoice_id' => 999999])
            ->assertStatus(422);

        // The DO is left unconverted.
        $this->assertNull(DB::table('deliveryorders')->find($do)->invoice_id);
    }

    /** @test */
    public function it_rejects_merging_into_a_non_combined_invoice()
    {
        // A plain (single-convert) invoice keeps the DO's own remark, never the "Combined from" marker.
        $invoiceId = $this->makeInvoice(['remark' => 'walk-in']);
        $do = $this->makeDo();

        $this->withoutMiddleware()
            ->postJson('/deliveryOrders/merge-convert', ['ids' => [$do], 'invoice_id' => $invoiceId])
            ->assertStatus(422);

        // Nothing is appended and the DO is left unconverted.
        $this->assertEquals(0, DB::table('invoice_details')->where('invoice_id', $invoiceId)->count());
        $this->assertNull(DB::table('deliveryorders')->find($do)->invoice_id);
    }

    /** @test */
    public function picker_lists_invoices_and_excludes_voided()
    {
        DB::table('customers')->insert(['id' => 1, 'company' => 'ABC Sdn Bhd']);
        $active = $this->makeInvoice(['invoiceno' => 'OX2608/00010', 'customer_id' => 1]);
        $this->makeInvoice(['invoiceno' => 'OX2608/00011', 'status' => Invoice::STATUS_VOIDED]);

        $response = $this->withoutMiddleware()
            ->getJson('/deliveryOrders/merge-invoices');

        $response->assertStatus(200)->assertJsonCount(1, 'results');
        $this->assertEquals($active, $response->json('results.0.id'));
        $this->assertStringContainsString('OX2608/00010', $response->json('results.0.text'));
        $this->assertStringContainsString('ABC Sdn Bhd', $response->json('results.0.text'));
    }

    /** @test */
    public function picker_excludes_non_combined_invoices()
    {
        $combined = $this->makeInvoice(['invoiceno' => 'OX2608/00020']);
        // Single-convert invoice — not a merge target.
        $this->makeInvoice(['invoiceno' => 'OX2608/00021', 'remark' => 'some customer note']);
        // No remark at all.
        $this->makeInvoice(['invoiceno' => 'OX2608/00022', 'remark' => null]);

        $response = $this->withoutMiddleware()
            ->getJson('/deliveryOrders/merge-invoices');

        $response->assertStatus(200)->assertJsonCount(1, 'results');
        $this->assertEquals($combined, $response->json('results.0.id'));
    }

    /** @test */
    public function picker_filters_invoices_by_search_term()
    {
        $match = $this->makeInvoice(['invoiceno' => 'OX2608/00042']);
        $this->makeInvoice(['invoiceno' => 'OX2608/09999']);

        $response = $this->withoutMiddleware()
            ->getJson('/deliveryOrders/merge-invoices?q=00042');

        $response->assertStatus(200)->assertJsonCount(1, 'results');
        $this->assertEquals($match, $response->json('results.0.id'));
    }
}
