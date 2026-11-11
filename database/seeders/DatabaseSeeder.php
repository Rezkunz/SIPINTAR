<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SipintarSeeder::class);
        $this->resetSequences();

        $this->call(UserSeeder::class);
        $this->resetSequences();

        $this->call(PinjamanKasDemoSeeder::class);
        $this->resetSequences();
    }

    /**
     * Setelah seeder mengisi ID manual, geser sequence PostgreSQL ke
     * ID tertinggi + 1 supaya insert berikutnya tidak bentrok.
     * Berlaku untuk semua tabel di schema public yang punya kolom auto-increment.
     */
    private function resetSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $kolom = DB::select("
            select table_name, column_name
            from information_schema.columns
            where table_schema = 'public'
              and (column_default like 'nextval(%' or is_identity = 'YES')
        ");

        foreach ($kolom as $k) {
            $tabel = str_replace('"', '""', $k->table_name);
            $col   = str_replace('"', '""', $k->column_name);

            $row = DB::selectOne(
                'select pg_get_serial_sequence(?, ?) as seq',
                ['public."' . $tabel . '"', $k->column_name]
            );

            if (!$row || empty($row->seq)) {
                continue;
            }

            DB::select(
                'select setval(?::regclass, coalesce((select max("' . $col . '") from public."' . $tabel . '"), 0) + 1, false)',
                [$row->seq]
            );
        }
    }
}