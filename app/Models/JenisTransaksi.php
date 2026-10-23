<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisTransaksi extends Model
{
    protected $table = 'tblJENIS_TRANSAKSI';
    protected $primaryKey = 'jenis_transaksi_id';
    public $timestamps = false;

    protected $fillable = [
        'nama_jenis',
    ];

    public function drafts(): HasMany
    {
        return $this->hasMany(Draft::class, 'jenis_transaksi_id', 'jenis_transaksi_id');
    }
}
