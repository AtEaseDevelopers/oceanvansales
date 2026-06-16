<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Driver;
use Illuminate\Support\Facades\Crypt;

class UpdateDriverRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = Crypt::decrypt($this->route('driver'));
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = [
            'employeeid' => ['nullable', 'string', 'max:20',
                Rule::unique('drivers', 'employeeid')->where('company_id', $companyId)->ignore($id),
            ],
            'password' => 'required|string|max:65535',
            'name' => 'required|string|max:255',
            'ic' => ['nullable', 'string', 'max:20',
                Rule::unique('drivers', 'ic')->where('company_id', $companyId)->ignore($id),
            ],
            'phone' => 'nullable|string|max:255',
            'bankdetails1' => 'nullable|string|max:255',
            'bankdetails2' => 'nullable|string|max:255',
            'firstvaccine' => 'nullable',
            'secondvaccine' => 'nullable',
            'temperature' => 'nullable|numeric',
            'status' => 'required',
            'remark' => 'nullable|string|max:255',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
            'deleted_at' => 'nullable',
        ];

        return $rules;
    }
}
