<?php

namespace App\Http\Requests\SiswaPraktik;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRekeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('siswa_praktik') === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('setoran_awal') && is_string($this->input('setoran_awal'))) {
            $clean = preg_replace('/[^\d]/', '', $this->input('setoran_awal'));
            $this->merge(['setoran_awal' => $clean !== '' ? $clean : 0]);
        }
    }

    public function rules(): array
    {
        return [
            'setoran_awal' => ['required', 'numeric', 'min:' . config('sipintar.setoran.min_awal', 20000)],
        ];
    }
}