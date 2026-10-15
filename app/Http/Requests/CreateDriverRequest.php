<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Driver;

class CreateDriverRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $companyId = app()->bound('current_company_id') ? app('current_company_id') : null;
        $rules = Driver::$rules;
        $rules['employeeid'] = ['required', 'string', 'max:20',
            Rule::unique('drivers', 'employeeid')->where('company_id', $companyId),
        ];
        $rules['ic'] = ['nullable', 'string', 'max:20',
            Rule::unique('drivers', 'ic')->where('company_id', $companyId),
        ];
        $rules['offline_series'] = ['nullable', 'regex:/^[A-Z]{1,3}$/', 'not_in:' . Driver::LEGACY_OFFLINE_SERIES,
            Rule::unique('drivers', 'offline_series')->where('company_id', $companyId),
        ];

        return $rules;
    }

    protected function prepareForValidation()
    {
        $this->merge(['offline_series' => strtoupper(trim((string) $this->input('offline_series'))) ?: null]);
    }

    public function messages()
    {
        return Driver::offlineSeriesMessages();
    }
}
