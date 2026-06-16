<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Agent;

class CreateAgentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = Agent::$rules;
        $rules['employeeid'] = ['required', 'string', 'max:20',
            Rule::unique('agents', 'employeeid')->where('company_id', $companyId),
        ];
        $rules['ic'] = ['nullable', 'string', 'max:20',
            Rule::unique('agents', 'ic')->where('company_id', $companyId),
        ];

        return $rules;
    }
}
