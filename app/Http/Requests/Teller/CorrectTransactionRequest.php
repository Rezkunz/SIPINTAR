<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class CorrectTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teller') === true;
    }

    public function rules(): array
    {
        return [
            'alasan' => ['required', 'string', 'max:500'],
            'jenis_transaksi_id' => ['required', 'exists:tblJENIS_TRANSAKSI,jenis_transaksi_id'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan.required'             => 'Alasan koreksi wajib diisi.',
            'alasan.max'                  => 'Alasan koreksi maksimal 500 karakter.',
            'jenis_transaksi_id.required' => 'Jenis transaksi koreksi wajib dipilih.',
            'jenis_transaksi_id.exists'   => 'Jenis transaksi koreksi tidak valid.',
            'nominal.required'            => 'Nominal koreksi wajib diisi.',
            'nominal.numeric'             => 'Nominal koreksi harus berupa angka.',
            'nominal.min'                 => 'Nominal koreksi harus lebih besar dari 0.',
        ];
    }
}