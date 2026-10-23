<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'tblUSER';
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role_id',
        'nasabah_id',
        'no_hp',
        'no_induk',
        'email',
        'password',
        'nama_user',
        'status_akun',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /** Alias accessor untuk 'name' agar kompatibel dengan template blade yang memanggil auth()->user()->name */
    public function getNameAttribute(): string
    {
        return $this->attributes['nama_user'] ?? '';
    }

    /** Alias accessor untuk 'role' agar kompatibel dengan middleware dan layout blade */
    public function getRoleAttribute(): string
    {
        return match ($this->role_id) {
            1 => 'teller',
            2 => 'bendahara',
            3 => 'bendahara_mini',
            4 => 'manager',
            5 => 'siswa_praktik',
            6 => 'nasabah',
            default => 'nasabah',
        };
    }

    /** Check if user has the given role slug. */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /** Check if user has any of the given roles. */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Human-readable role label in Indonesian. */
    public function roleName(): string
    {
        return match ($this->role_id) {
            1 => 'Teller',
            2 => 'Bendahara',
            3 => 'Bendahara Bank Mini',
            4 => 'Manager',
            5 => 'Siswa Praktik',
            6 => 'Nasabah',
            default => 'Pengguna',
        };
    }

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function nasabah()
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id', 'nasabah_id');
    }

    public function drafts()
    {
        return $this->hasMany(Draft::class, 'siswa_id', 'user_id');
    }
}
