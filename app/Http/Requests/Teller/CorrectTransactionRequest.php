<?php

namespace App\Http\Requests\Teller;

use Illuminate\Foundation\Http\FormRequest;

class CorrectTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('teller') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nominal') && is_string($this->input('nominal'))) {
            $raw = trim($this->input('nominal'));
            // Jika format Indonesia (mis. 500.000 atau 500.000,50), ubah ke angka murni
            if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $raw)) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } elseif (str_contains($raw, ',') && !str_contains($raw, '.')) {
                $raw = str_replace(',', '.', $raw);
            }
            $this->merge(['nominal' => $raw]);
        }
    }

    public function rules(): array
    {
        return [
            'no_rek' => ['nullable', 'string', 'max:20'],
            'alasan' => ['required', 'string', 'max:500'],
            'jenis_transaksi_id' => ['required', 'exists:tblJENIS_TRANSAKSI,jenis_transaksi_id'],
            'nominal' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}