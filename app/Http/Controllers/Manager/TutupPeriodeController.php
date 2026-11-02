<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\TutupPeriodeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class TutupPeriodeController extends Controller
{
    public function __construct(private TutupPeriodeService $service)
    {
    }

    /**
     * Daftar periode bulanan + detail periode terpilih (default: bulan lalu, yang biasanya siap ditutup).
     */
    public function index(Request $request)
    {
        $daftar = $this->service->daftarPeriode();

        $awalBulan = $this->periodeDariRequest($request) ?? now()->subMonthNoOverflow()->startOfMonth();
        $terpilih = $daftar->firstWhere('kunci', $awalBulan->format('Y-m'))
            ?? $daftar->first();
        $awalBulan = $terpilih->awal_bulan;

        $periodeTutupTerakhir = $daftar->firstWhere('status', 'tutup');

        return view('manager.periode.index', [
            'daftar'        => $daftar,
            'terpilih'      => $terpilih,
            'awalBulan'     => $awalBulan,
            'ringkasan'     => $this->service->ringkasan($awalBulan),
            'periksa'       => $this->service->periksa($awalBulan),
            'riwayat'       => $this->service->riwayat(),
            'bisaDibuka'    => $terpilih->status === 'tutup' && $periodeTutupTerakhir?->kunci === $terpilih->kunci,
            'jumlahTutup'   => $daftar->where('status', 'tutup')->count(),
            'jumlahBuka'    => $daftar->where('status', 'buka')->count(),
        ]);
    }

    /**
     * Proses tutup buku periode.
     */
    public function tutup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun'      => 'required|integer|min:2000|max:2100',
            'bulan'      => 'required|integer|min:1|max:12',
            'konfirmasi' => 'accepted',
            'catatan'    => 'nullable|string|max:255',
        ], [
            'konfirmasi.accepted' => 'Centang pernyataan bahwa seluruh transaksi bulan ini sudah diperiksa sebelum menutup periode.',
        ]);

        $awalBulan = Carbon::create($data['tahun'], $data['bulan'], 1);

        try {
            $this->service->tutup($awalBulan, $data['catatan'] ?? null);
        } catch (RuntimeException $e) {
            return $this->kembali($awalBulan)->with('error', $e->getMessage());
        }

        return $this->kembali($awalBulan)->with(
            'success',
            'Periode ' . $awalBulan->translatedFormat('F Y') . ' berhasil ditutup. Saldo kas dibekukan dan transaksi bertanggal di bulan ini kini terkunci.'
        );
    }

    /**
     * Buka kembali periode tertutup terakhir (wajib beralasan, tercatat di audit log).
     */
    public function bukaKembali(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tahun'  => 'required|integer|min:2000|max:2100',
            'bulan'  => 'required|integer|min:1|max:12',
            'alasan' => 'required|string|min:10|max:255',
        ], [
            'alasan.required' => 'Alasan membuka kembali periode wajib diisi.',
            'alasan.min'      => 'Alasan minimal 10 karakter agar jelas untuk keperluan audit.',
        ]);

        $awalBulan = Carbon::create($data['tahun'], $data['bulan'], 1);

        try {
            $this->service->bukaKembali($awalBulan, trim($data['alasan']));
        } catch (RuntimeException $e) {
            return $this->kembali($awalBulan)->with('error', $e->getMessage());
        }

        return $this->kembali($awalBulan)->with(
            'success',
            'Periode ' . $awalBulan->translatedFormat('F Y') . ' dibuka kembali. Jangan lupa menutupnya lagi setelah koreksi selesai.'
        );
    }

    private function periodeDariRequest(Request $request): ?Carbon
    {
        $tahun = $request->integer('tahun');
        $bulan = $request->integer('bulan');

        return ($tahun >= 2000 && $tahun <= 2100 && $bulan >= 1 && $bulan <= 12)
            ? Carbon::create($tahun, $bulan, 1)
            : null;
    }

    private function kembali(Carbon $awalBulan): RedirectResponse
    {
        return redirect()->route('periode.tutup', ['tahun' => $awalBulan->year, 'bulan' => $awalBulan->month]);
    }
}
