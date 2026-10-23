<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nasabah extends Model
{
    protected $table = 'tblNASABAH';
    protected $primaryKey = 'nasabah_id';
    public $timestamps = false;

    protected $fillable = [
        'no_hp',
        'nama_nasabah',
        'jenis_nasabah',
        'kelas_unit',
        'status_nasabah',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'nasabah_id', 'nasabah_id');
    }

    public function rekening()
    {
        return $this->hasOne(Rekening::class, 'nasabah_id', 'nasabah_id');
    }

    public function drafts()
    {
        return $this->hasManyThrough(Draft::class, Rekening::class, 'nasabah_id', 'rekening_id', 'nasabah_id', 'rekening_id');
    }
}
