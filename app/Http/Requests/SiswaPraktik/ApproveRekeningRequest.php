<?php

namespace App\Http\Requests\SiswaPraktik;

use Illuminate\Foundation\Http\FormRequest;

class ApproveRekeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('siswa_praktik') === true;
    }

    public function rules(): array
    {
        return [
            'setoran_awal' => ['required', 'numeric', 'min:10000'],
        ];
    }
}