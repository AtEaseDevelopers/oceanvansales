<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
        // Code is intentionally not unique — duplicate codes across customers are allowed.
        $rules['code'] = ['required', 'string', 'max:255'];

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }

        return $rules;
    }
}
