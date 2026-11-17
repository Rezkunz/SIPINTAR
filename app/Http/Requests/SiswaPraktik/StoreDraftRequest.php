<?php

namespace App\Http\Requests\SiswaPraktik;

use Illuminate\Foundation\Http\FormRequest;

class StoreDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('siswa_praktik');
    }

    /**
     * Prepare data for validation (membersihkan pemisah ribuan titik bila ada).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('nominal') && is_string($this->nominal)) {
            // Hapus prefix Rp, spasi, dan titik pemisah ribuan; tanda minus DIPERTAHANKAN
            // agar nominal negatif ditolak aturan min (bukan diam-diam menjadi positif)
            $cleanNominal = preg_replace('/[^\d\-]/', '', $this->nominal);
            $this->merge([
                'nominal' => match (true) {
                    $cleanNominal === ''                         => null,
                    (bool) preg_match('/^-?\d+$/', $cleanNominal) => (float) $cleanNominal,
                    default                                      => $this->nominal, // ditolak aturan numeric
                },
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $min = config('sipintar.draft.min_nominal', 1000);
        $max = config('sipintar.draft.max_nominal', 5000000);

        return [
            'no_rek'     => ['required', 'string', 'exists:tblREKENING,no_rek'],
            'jenis'      => ['required', 'in:setoran,penarikan'],
            'nominal'    => ['required', 'numeric', "min:{$min}", "max:{$max}"],
        ];
    }

    /**
     * Custom validation messages in standard Indonesian.
     */
    public function messages(): array
    {
        $minFormatted = number_format(config('sipintar.draft.min_nominal', 1000), 0, ',', '.');
        $maxFormatted = number_format(config('sipintar.draft.max_nominal', 5000000), 0, ',', '.');

        return [
            'no_rek.required'  => 'Nomor rekening wajib dipilih atau diisi.',
            'no_rek.exists'    => 'Nomor rekening yang dipilih tidak terdaftar di sistem.',
            'jenis.required'   => 'Jenis transaksi wajib dipilih.',
            'jenis.in'         => 'Jenis transaksi harus berupa Setoran atau Penarikan.',
            'nominal.required' => 'Nominal transaksi wajib diisi.',
            'nominal.numeric'  => 'Nominal transaksi harus berupa angka yang valid.',
            'nominal.min'      => "Nominal transaksi minimal adalah Rp {$minFormatted}.",
            'nominal.max'      => "Nominal transaksi maksimal per draft adalah Rp {$maxFormatted}.",
        ];
    }
}
