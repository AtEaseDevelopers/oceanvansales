<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;
use Illuminate\Support\Facades\Crypt;
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
        $id = Crypt::decrypt($this->route('customer'));
        $rules = [
            // Code must be globally unique (maps 1:1 to an AutoCount debtor AccNo).
            'code' => ['required', 'string', 'max:255',
                Rule::unique('customers', 'code')->ignore($id),
            ],
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
