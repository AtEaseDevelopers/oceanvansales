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
        $rules = Customer::$rules;
        // Code must be globally unique: it maps 1:1 to an AutoCount debtor AccNo, and the
        // plugin auto-creates a debtor from it, so the same code must never point at two
        // different customers (even across companies).
        $rules['code'] = ['required', 'string', 'max:255',
            Rule::unique('customers', 'code'),
        ];

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }

        return $rules;
    }
}
