<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Lorry;

class CreateLorryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = Lorry::$rules;
        $rules['lorryno'] = ['required', 'string', 'max:255',
            Rule::unique('lorrys', 'lorryno')->where('company_id', $companyId),
        ];

        return $rules;
    }
}
