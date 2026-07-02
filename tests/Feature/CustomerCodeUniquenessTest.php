<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\CreateCustomerRequest;
use App\Services\EInvoiceService;

/**
 * Customer "code" maps 1:1 to an AutoCount debtor AccNo (the plugin auto-creates a debtor
 * from it), so it must be globally unique -- not just unique per company.
 *
 * The project has no create_users_table migration (dev uses an existing MySQL DB), so we
 * build just the customers table this rule touches instead of RefreshDatabase.
 */
class CustomerCodeUniquenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('company')->nullable();
            $table->integer('company_id')->nullable();
            $table->string('paymentterm')->nullable();
            $table->integer('status')->default(1);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('customers');
        parent::tearDown();
    }

    private function codeRules(): array
    {
        return (new CreateCustomerRequest(new EInvoiceService()))->rules();
    }

    /** @test */
    public function code_must_be_unique_across_all_companies()
    {
        DB::table('customers')->insert([
            'code' => 'DUP-1', 'company' => 'First', 'company_id' => 1, 'paymentterm' => '1',
        ]);

        // Same code under a DIFFERENT company must now be rejected.
        $validator = Validator::make(
            ['code' => 'DUP-1', 'company' => 'Second', 'company_id' => 2, 'paymentterm' => '1', 'status' => 1],
            $this->codeRules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('code', $validator->errors()->toArray());
    }

    /** @test */
    public function a_fresh_code_still_passes()
    {
        DB::table('customers')->insert([
            'code' => 'DUP-1', 'company' => 'First', 'company_id' => 1, 'paymentterm' => '1',
        ]);

        $validator = Validator::make(
            ['code' => 'NEW-1', 'company' => 'Second', 'company_id' => 2, 'paymentterm' => '1', 'status' => 1],
            $this->codeRules()
        );

        $this->assertFalse($validator->errors()->has('code'));
    }
}
