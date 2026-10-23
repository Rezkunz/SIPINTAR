<?php

namespace App\Http\Requests\SiswaPraktik;

use Illuminate\Foundation\Http\FormRequest;

class BukaRekeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('siswa_praktik');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama_nasabah'  => ['required', 'string', 'max:100'],
            'jenis_nasabah' => ['required', 'in:siswa,pegawai'],
            'no_induk'      => ['required', 'string', 'max:30'],
            'kelas_unit'    => ['nullable', 'string', 'max:50'],
            'no_hp'         => ['required', 'string', 'max:15', 'unique:tblNASABAH,no_hp'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nama_nasabah.required'  => 'Nama nasabah wajib diisi.',
            'nama_nasabah.max'       => 'Nama nasabah tidak boleh melebihi 100 karakter.',
            'jenis_nasabah.required' => 'Jenis nasabah wajib dipilih.',
            'jenis_nasabah.in'       => 'Jenis nasabah harus berupa Siswa atau Pegawai.',
            'no_induk.required'      => 'Nomor Induk (NISN/NIP) wajib diisi.',
            'no_induk.max'           => 'Nomor Induk tidak boleh melebihi 30 karakter.',
            'kelas_unit.max'         => 'Kelas/Unit kerja tidak boleh melebihi 50 karakter.',
            'no_hp.required'         => 'Nomor HP/WhatsApp wajib diisi.',
            'no_hp.max'              => 'Nomor HP tidak boleh melebihi 15 digit.',
            'no_hp.unique'           => 'Nomor HP/WhatsApp ini sudah terdaftar sebagai nasabah.',
        ];
    }
}
