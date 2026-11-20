<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class StoreTellerTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teller') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nominal') && is_string($this->input('nominal'))) {
            $clean = preg_replace('/[^\d]/', '', $this->input('nominal'));
            $this->merge(['nominal' => $clean !== '' ? $clean : 0]);
        }
    }

    public function rules(): array
    {
        return [
            'no_rek'  => ['required', 'string', 'exists:tblREKENING,no_rek'],
            'nominal' => ['required', 'numeric', 'min:' . config('sipintar.setoran.min_tanpa_batas', 1)],
            'abaikan_minimal' => ['nullable', 'boolean'],
        ];
    }
}