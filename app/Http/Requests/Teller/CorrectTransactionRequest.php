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
}