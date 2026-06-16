<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Lorry;
use Illuminate\Support\Facades\Crypt;

class UpdateLorryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = Crypt::decrypt($this->route('lorry'));
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = [
            'lorryno' => ['required', 'string', 'max:255',
                Rule::unique('lorrys', 'lorryno')->where('company_id', $companyId)->ignore($id),
            ],
            'status' => 'required',
            'remark' => 'nullable|string|max:255',
            'created_at' => 'nullable',
            'updated_at' => 'nullable',
            'deleted_at' => 'nullable',
        ];

        return $rules;
    }
}
