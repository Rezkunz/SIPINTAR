<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draft extends Model
{
    protected $table = 'tblDRAFT';
    protected $primaryKey = 'draft_id';
    public $timestamps = false;

    protected $fillable = [
        'rekening_id',
        'jenis_transaksi_id',
        'siswa_id',
        'teller_id',
        'nominal',
        'tgl_input',
        'status_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tgl_input' => 'datetime',
        ];
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(Rekening::class, 'rekening_id', 'rekening_id');
    }

    public function jenisTransaksi(): BelongsTo
    {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_transaksi_id', 'jenis_transaksi_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id', 'user_id');
    }

    public function teller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teller_id', 'user_id');
    }
}
