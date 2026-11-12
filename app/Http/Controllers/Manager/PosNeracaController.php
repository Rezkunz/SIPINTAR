<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosNeracaController extends Controller
{
    /**
     * Tambah entri pos neraca manual.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);

        $posId = DB::table('tblPOS_NERACA')->insertGetId(array_merge($data, [, 'pos_neraca_id')
            'user_id'    => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]), 'pos_neraca_id');

        AuditLogService::log(aksi: 'INSERT', entitas: 'tblPOS_NERACA', entitasId: $posId, dataBaru: $data);

        return redirect()
            ->route('laporan.neraca', $this->paramKembali($request))
            ->with('success', "Pos \"{$data['nama_pos']}\" berhasil ditambahkan ke neraca.")
            ->withFragment('pos-manual');
    }

    /**
     * Ubah entri pos neraca manual.
     */
    public function update(Request $request, int $pos): RedirectResponse
    {
        $lama = DB::table('tblPOS_NERACA')->where('pos_neraca_id', $pos)->first();
        abort_unless($lama, 404);

        $data = $this->validasi($request);

        DB::table('tblPOS_NERACA')->where('pos_neraca_id', $pos)->update(array_merge($data, ['updated_at' => now()]));

        AuditLogService::log(
            aksi: 'UPDATE',
            entitas: 'tblPOS_NERACA',
            entitasId: $pos,
            dataLama: (array) $lama,
            dataBaru: $data
        );

        return redirect()
            ->route('laporan.neraca', $this->paramKembali($request))
            ->with('success', "Pos \"{$data['nama_pos']}\" berhasil diperbarui.")
            ->withFragment('pos-manual');
    }

    /**
     * Hapus entri pos neraca manual.
     */
    public function destroy(Request $request, int $pos): RedirectResponse
    {
        $lama = DB::table('tblPOS_NERACA')->where('pos_neraca_id', $pos)->first();
        abort_unless($lama, 404);

        DB::table('tblPOS_NERACA')->where('pos_neraca_id', $pos)->delete();

        AuditLogService::log(aksi: 'DELETE', entitas: 'tblPOS_NERACA', entitasId: $pos, dataLama: (array) $lama);

        return redirect()
            ->route('laporan.neraca', $this->paramKembali($request))
            ->with('success', "Pos \"{$lama->nama_pos}\" berhasil dihapus dari neraca.")
            ->withFragment('pos-manual');
    }

    private function validasi(Request $request): array
    {
        $data = $request->validate([
            'kelompok'    => 'required|in:aktiva,kewajiban,ekuitas',
            'nama_pos'    => 'required|string|max:100',
            'nilai'       => 'required|numeric|between:-9999999999999,9999999999999',
            'tgl_berlaku' => 'required|date',
            'keterangan'  => 'nullable|string|max:255',
        ], [
            'kelompok.required'    => 'Kelompok pos (Aktiva / Kewajiban / Ekuitas) wajib dipilih.',
            'nama_pos.required'    => 'Nama pos wajib diisi, misalnya "Deposito BPR".',
            'nilai.required'       => 'Nilai pos wajib diisi.',
            'nilai.numeric'        => 'Nilai pos harus berupa angka (boleh negatif untuk akumulasi penyusutan).',
            'tgl_berlaku.required' => 'Tanggal berlaku wajib diisi.',
        ]);

        $data['nama_pos'] = trim($data['nama_pos']);

        return $data;
    }

    /**
     * Kembali ke periode neraca yang sedang dibuka.
     */
    private function paramKembali(Request $request): array
    {
        return array_filter([
            'bulan' => $request->integer('kembali_bulan') ?: null,
            'tahun' => $request->integer('kembali_tahun') ?: null,
        ]);
    }
}
