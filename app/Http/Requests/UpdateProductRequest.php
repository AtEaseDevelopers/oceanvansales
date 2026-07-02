<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;
use Illuminate\Support\Facades\Crypt;
use App\Services\EInvoiceService;

class UpdateProductRequest extends FormRequest
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
        $id = Crypt::decrypt($this->route('product'));
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = [
            'code' => ['required', 'string', 'max:255',
                Rule::unique('products', 'code')->where('company_id', $companyId)->ignore($id),
            ],
            'name' => 'required|string|max:255',
            'price' => 'nullable|numeric',
            'status' => 'required',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
        ];

        if ($this->eInvoiceService->isEnabled()) {
            $rules = array_merge($rules, $this->eInvoiceService->requiredProductFields());
        }

        return $rules;
    }
}
