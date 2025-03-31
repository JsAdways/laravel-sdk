<?php

namespace Jsadways\LaravelSDK\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return True;
    }

    # 驗證規則
    public function rules(): array
    {
        return [
            'filter' => 'json|nullable',
            'sort_by' => 'string|nullable',
            'sort_order' => 'string|in:asc,desc|nullable',
            'per_page' => 'integer|nullable',
            'extra' => 'json|nullable',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['filter' => $this->input('filter', '{}'), 'extra' => $this->input('extra', '{}')]);
    }
}
