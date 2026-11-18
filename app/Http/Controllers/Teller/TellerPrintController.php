<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Rekening;
use App\Support\Terbilang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TellerPrintController extends Controller
{
    public function slipSetoran(int $transaksiId): View
    {
        $trx = $this->findTransaction($transaksiId);
        $nominal = (float) ($trx->kredit > 0 ? $trx->kredit : $trx->debit);
        $terbilang = Terbilang::rupiah($nominal);

        return view('teller.cetak.slip_setoran', compact('trx', 'nominal', 'terbilang'));
    }

    public function slipPengambilan(Request $request, int $transaksiId): View
    {
        $trx = $this->findTransaction($transaksiId);
        $nominal = (float) ($trx->debit > 0 ? $trx->debit : $trx->kredit);
        $terbilang = Terbilang::rupiah($nominal);
        $noKk = trim((string) $request->query('no_kk', ''));

        return view('teller.cetak.slip_pengambilan', compact('trx', 'nominal', 'terbilang', 'noKk'));
    }

    public function bukuTabungan(Request $request): View
    {
        $noRek = trim((string) $request->query('no_rek', ''));
        $rekening = Rekening::with('nasabah')->where('no_rek', $noRek)->firstOrFail();

        $lineOffset = max(1, min(60, (int) $request->query('line_offset', 1)));
        $dariId = (int) $request->query('dari_id', 0);
        $modeCetak = $request->query('mode_cetak') === 'isi' ? 'isi' : 'full';

        $sandiMap = config('sipintar.sandi', [
            'setoran' => 'S1',
            'penarikan' => 'S2',
            'jasa_tabungan' => 'S3',
            'koreksi' => 'K1',
        ]);

        $mutasi = DB::table('tblTRANSAKSI as t')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->leftJoin('tblUSER as u', 't.user_id', '=', 'u.user_id')
            ->leftJoin('tblAUDIT_LOG as al', function ($join) {
                $join->on('al.entitas_id', '=', 't.transaksi_id')
                    ->where('al.entitas', '=', 'tblTRANSAKSI')
                    ->where('al.aksi', '=', 'KOREKSI_TRANSAKSI');
            })
            ->where('t.rekening_id', $rekening->rekening_id)
            ->when($dariId > 0, fn ($q) => $q->where('t.transaksi_id', '>=', $dariId))
            ->select(
                't.*',
                'jt.nama_jenis',
                'u.no_induk as kode_teller',
                'al.audit_log_id as koreksi_id'
            )
            ->orderBy('t.tgl_transaksi')
            ->orderBy('t.transaksi_id')
            ->get()
            ->map(function ($row) use ($sandiMap) {
                $row->sandi = $row->koreksi_id
                    ? ($sandiMap['koreksi'] ?? 'K1')
                    : ($sandiMap[$row->nama_jenis] ?? strtoupper(substr($row->nama_jenis, 0, 2)));
                return $row;
            });

        $semuaTransaksi = DB::table('tblTRANSAKSI')
            ->where('rekening_id', $rekening->rekening_id)
            ->orderBy('transaksi_id')
            ->get(['transaksi_id', 'tgl_transaksi', 'debit', 'kredit']);

        return view('teller.cetak.buku_tabungan', compact(
            'rekening',
            'mutasi',
            'semuaTransaksi',
            'lineOffset',
            'dariId',
            'modeCetak',
            'sandiMap'
        ));
    }

    private function findTransaction(int $transaksiId): object
    {
        $trx = DB::table('tblTRANSAKSI as t')
            ->join('tblREKENING as r', 't.rekening_id', '=', 'r.rekening_id')
            ->join('tblNASABAH as n', 'r.nasabah_id', '=', 'n.nasabah_id')
            ->join('tblJENIS_TRANSAKSI as jt', 't.jenis_transaksi_id', '=', 'jt.jenis_transaksi_id')
            ->leftJoin('tblUSER as u', 't.user_id', '=', 'u.user_id')
            ->leftJoin('tblBUKTI as b', 't.transaksi_id', '=', 'b.transaksi_id')
            ->where('t.transaksi_id', $transaksiId)
            ->select(
                't.*',
                'r.no_rek',
                'n.nama_nasabah',
                'n.no_hp',
                'n.kelas_unit',
                'n.jenis_nasabah',
                'jt.nama_jenis',
                'u.nama_user as nama_kasir',
                'b.no_bukti'
            )
            ->first();

        abort_unless($trx, 404);

        return $trx;
    }
}
