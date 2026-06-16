<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Kelindan;

class CreateKelindanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = Kelindan::$rules;
        $rules['employeeid'] = ['required', 'string', 'max:20',
            Rule::unique('kelindans', 'employeeid')->where('company_id', $companyId),
        ];
        $rules['ic'] = ['nullable', 'string', 'max:20',
            Rule::unique('kelindans', 'ic')->where('company_id', $companyId),
        ];

        return $rules;
    }
}
