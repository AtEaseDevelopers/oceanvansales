<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Customer;
use App\Services\EInvoiceService;

class UpdateCustomerRequest extends FormRequest
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
        $rules = [
            // Code is intentionally not unique — duplicate codes across customers are allowed.
            'code' => ['required', 'string', 'max:255'],
            'company' => 'required|string|max:255',
            'paymentterm' => 'required',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:65535',
            'status' => 'required',
            'is_do' => 'boolean',
            'customer_code' => 'nullable|string|max:255',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
        ];

        foreach (Customer::pairedRules() as $field => $extraRules) {
            $existing = $rules[$field] ?? [];
            $rules[$field] = array_merge(is_string($existing) ? explode('|', $existing) : $existing, $extraRules);
        }

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }

        return $rules;
    }
}
