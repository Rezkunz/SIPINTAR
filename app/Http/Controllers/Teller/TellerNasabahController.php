<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\Rekening;
use App\Services\RekeningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan nasabah & rekening oleh Teller di loket:
 * buka rekening langsung, ubah data nasabah, dan tutup rekening.
 */
class TellerNasabahController extends Controller
{
    public function create(): View
    {
        return view('teller.nasabah_form', ['nasabah' => null]);
    }

    public function store(Request $request, RekeningService $service): RedirectResponse
    {
        $data = $request->validate([
            'nama_nasabah'  => ['required', 'string', 'max:100'],
            'jenis_nasabah' => ['required', 'in:siswa,pegawai'],
            'kelas_unit'    => ['nullable', 'string', 'max:50'],
            'no_hp'         => ['required', 'string', 'max:15', 'regex:/^[0-9+]+$/', 'unique:tblNASABAH,no_hp', 'unique:tblUSER,no_hp'],
            'setoran_awal'  => ['required', 'numeric', 'min:10000'],
        ], $this->pesan() + [
            'setoran_awal.required' => 'Setoran awal wajib diisi.',
            'setoran_awal.numeric'  => 'Setoran awal harus berupa angka.',
            'setoran_awal.min'      => 'Setoran awal minimal Rp 10.000.',
        ]);

        $rekening = $service->bukaRekeningLoket($data, (int) auth()->id());

        return redirect()->route('tabungan.buka-rekening')->with(
            'success',
            "Nasabah {$rekening->nasabah->nama_nasabah} (ID Nasabah #{$rekening->nasabah_id}) dan rekening {$rekening->no_rek} berhasil dibuka dengan setoran awal Rp "
                . number_format((float) $rekening->saldo, 0, ',', '.') . '.'
        );
    }

    public function edit(Nasabah $nasabah): View
    {
        $nasabah->load('rekening');

        return view('teller.nasabah_form', compact('nasabah'));
    }

    public function update(Request $request, Nasabah $nasabah, RekeningService $service): RedirectResponse
    {
        $data = $request->validate([
            'nama_nasabah' => ['required', 'string', 'max:100'],
            'kelas_unit'   => ['nullable', 'string', 'max:50'],
            'no_hp'        => [
                'required', 'string', 'max:15', 'regex:/^[0-9+]+$/',
                Rule::unique('tblNASABAH', 'no_hp')->ignore($nasabah->nasabah_id, 'nasabah_id'),
            ],
        ], $this->pesan());

        $service->ubahNasabah($nasabah->nasabah_id, $data);

        return redirect()->route('tabungan.buka-rekening', ['status' => 'semua'])
            ->with('success', "Data nasabah {$data['nama_nasabah']} berhasil diperbarui.");
    }

    public function tutup(Rekening $rekening, RekeningService $service): RedirectResponse
    {
        $service->tutupRekening($rekening->rekening_id);

        return redirect()->route('tabungan.buka-rekening', ['status' => 'tutup'])
            ->with('success', "Rekening {$rekening->no_rek} berhasil ditutup. Riwayat transaksinya tetap dapat dilihat.");
    }

    private function pesan(): array
    {
        return [
            'nama_nasabah.required'  => 'Nama nasabah wajib diisi.',
            'nama_nasabah.max'       => 'Nama nasabah maksimal 100 karakter.',
            'jenis_nasabah.required' => 'Jenis nasabah wajib dipilih.',
            'jenis_nasabah.in'       => 'Jenis nasabah harus Siswa atau Pegawai.',
            'kelas_unit.max'         => 'Kelas/unit maksimal 50 karakter.',
            'no_hp.required'         => 'Nomor HP/WhatsApp wajib diisi.',
            'no_hp.max'              => 'Nomor HP maksimal 15 digit.',
            'no_hp.regex'            => 'Nomor HP hanya boleh berisi angka.',
            'no_hp.unique'           => 'Nomor HP/WhatsApp ini sudah terdaftar.',
        ];
    }
}
