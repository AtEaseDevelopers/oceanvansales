<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
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
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
        ];

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredFields());
        }

        return $rules;
    }
}
