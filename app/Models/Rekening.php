<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rekening extends Model
{
    protected $table = 'tblREKENING';
    protected $primaryKey = 'rekening_id';
    public $timestamps = false;

    protected $fillable = [
        'no_rek',
        'nasabah_id',
        'tgl_buka',
        'saldo',
        'status_rekening',
    ];

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id', 'nasabah_id');
    }
}
