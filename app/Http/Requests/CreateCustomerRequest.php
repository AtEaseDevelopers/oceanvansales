<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;
use App\Services\EInvoiceService;

class CreateCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected $eInvoiceService;

    public function __construct(EInvoiceService $eInvoiceService)
    {
        $this->eInvoiceService = $eInvoiceService;
    }

    public function rules()
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = Customer::$rules;
        $rules['code'] = ['required', 'string', 'max:255',
            Rule::unique('customers', 'code')->where('company_id', $companyId),
        ];

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }

        return $rules;
    }
}
