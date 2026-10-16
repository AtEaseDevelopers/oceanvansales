<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\Invoice;

/**
 * Covers the AutoCount status column filter on the invoice listing DataTable.
 *
 * The listing is a Yajra server-side DataTable; filtering is driven by a per-column
 * search value sent in the ajax request. These tests hit the index endpoint with that
 * column search payload and assert only invoices in the chosen AutoCount state come back.
 *
 * As with AutocountSyncTest, the project has no create_*_table migrations for its base
 * schema, so we build just the tables the listing query touches instead of RefreshDatabase.
 */
class InvoiceAutocountFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('invoiceno')->nullable();
            $table->date('date')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('driver_id')->nullable();
            $table->integer('kelindan_id')->nullable();
            $table->integer('supervisor_id')->nullable();
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
            $table->integer('product_id')->nullable();
            $table->integer('quantity')->nullable();
            $table->float('price', 10, 2)->nullable();
            $table->timestamps();
        });

        // Relations the listing query eager-loads; empty is fine (invoices carry null FKs).
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('company')->nullable();
        });
        Schema::create('drivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });
        Schema::create('kelindans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });
        Schema::create('supervisors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('invoice_details');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('kelindans');
        Schema::dropIfExists('supervisors');
        parent::tearDown();
    }

    /** Seed one invoice in each of the four AutoCount states, keyed by invoiceno. */
    private function seedOnePerState(): void
    {
        $states = [
            'NOTSYNCED' => Invoice::AUTOCOUNT_NOT_SYNCED,
            'QUEUED'    => Invoice::AUTOCOUNT_QUEUED,
            'SYNCED'    => Invoice::AUTOCOUNT_SYNCED,
            'FAILED'    => Invoice::AUTOCOUNT_FAILED,
        ];

        foreach ($states as $no => $status) {
            DB::table('invoices')->insert([
                'invoiceno'        => $no,
                'date'             => '2026-07-01',
                'status'           => 1,
                'autocount_status' => $status,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }

    /**
     * Build the DataTables ajax query params for the invoice listing with a search value
     * on the AutoCount status column (index 9). Column order mirrors InvoiceDataTable
     * getColumns() with the e-invoice column disabled.
     */
    private function listingParams(string $autocountSearch): array
    {
        $col = function (string $data, string $name, bool $searchable, string $value = '') {
            return [
                'data'       => $data,
                'name'       => $name,
                'searchable' => $searchable ? 'true' : 'false',
                'orderable'  => 'true',
                'search'     => ['value' => $value, 'regex' => 'false'],
            ];
        };

        $columns = [
            $col('id', 'id', false),
            $col('invoiceno', 'invoiceno', true),
            $col('date', 'date', true),
            $col('customer.company', 'customer.company', true),
            $col('driver.name', 'driver.name', true),
            $col('kelindan.name', 'kelindan.name', true),
            $col('invoicedetail', 'invoicedetail', false),
            $col('paymentterm', 'invoices.paymentterm', true),
            $col('status', 'invoices.status', true),
            $col('autocount_status', 'invoices.autocount_status', true, $autocountSearch),
            $col('action', 'action', false),
        ];

        return [
            'draw'   => 1,
            'start'  => 0,
            'length' => 100,
            'search' => ['value' => '', 'regex' => 'false'],
            'order'  => [],
            'columns' => $columns,
        ];
    }

    /** Return the invoiceno values the listing returns when filtered to $autocountSearch. */
    private function filteredInvoiceNos(string $autocountSearch): array
    {
        $response = $this->withoutMiddleware()
            ->getJson('/invoices?' . http_build_query($this->listingParams($autocountSearch)));

        $response->assertStatus(200);

        return collect($response->json('data'))->pluck('invoiceno')->sort()->values()->all();
    }

    /** @test */
    public function filtering_by_failed_returns_only_failed_invoices()
    {
        $this->seedOnePerState();

        $this->assertEquals(['FAILED'], $this->filteredInvoiceNos((string) Invoice::AUTOCOUNT_FAILED));
    }

    /** @test */
    public function filtering_by_queued_returns_only_queued_invoices()
    {
        $this->seedOnePerState();

        // Exact match: value "1" must not also pull Not Synced (0)/Synced (2)/Failed (3).
        $this->assertEquals(['QUEUED'], $this->filteredInvoiceNos((string) Invoice::AUTOCOUNT_QUEUED));
    }

    /** @test */
    public function filtering_by_not_synced_returns_only_not_synced_invoices()
    {
        $this->seedOnePerState();

        $this->assertEquals(['NOTSYNCED'], $this->filteredInvoiceNos((string) Invoice::AUTOCOUNT_NOT_SYNCED));
    }

    /** @test */
    public function no_autocount_filter_returns_every_invoice()
    {
        $this->seedOnePerState();

        $this->assertEquals(
            ['FAILED', 'NOTSYNCED', 'QUEUED', 'SYNCED'],
            $this->filteredInvoiceNos('')
        );
    }
}
