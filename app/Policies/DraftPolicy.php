<?php

namespace App\Policies;

use App\Models\Draft;
use App\Models\User;

class DraftPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat draft tertentu.
     * Siswa praktik hanya boleh melihat draft yang ia input sendiri.
     */
    public function view(User $user, Draft $draft): bool
    {
        return (int) $user->user_id === (int) $draft->siswa_id;
    }

    /**
     * Tentukan apakah pengguna dapat membuat draft baru.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('siswa_praktik');
    }
}
