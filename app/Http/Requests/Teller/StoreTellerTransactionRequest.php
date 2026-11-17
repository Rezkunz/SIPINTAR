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

    public function messages(): array
    {
        $min = number_format(config('sipintar.draft.min_nominal', 1000), 0, ',', '.');

        return [
            'no_rek.required'  => 'Nomor rekening wajib dipilih.',
            'no_rek.exists'    => 'Nomor rekening yang dipilih tidak terdaftar di sistem.',
            'nominal.required' => 'Nominal transaksi wajib diisi.',
            'nominal.numeric'  => 'Nominal transaksi harus berupa angka.',
            'nominal.min'      => "Nominal transaksi minimal Rp {$min}.",
        ];
    }
}