<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreTellerTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teller') === true;
    }

    public function rules(): array
    {
        return [
            'no_rek'  => ['required', 'string', 'exists:tblREKENING,no_rek'],
            'nominal' => ['required', 'numeric', 'min:' . config('sipintar.draft.min_nominal', 1000)],
        ];
    }
}