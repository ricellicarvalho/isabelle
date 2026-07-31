<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE events MODIFY COLUMN tipo ENUM('avaliacao_nr1','devolutiva','treinamento','palestra','reuniao','formacao_humana','outro') NOT NULL DEFAULT 'outro'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        DB::statement("ALTER TABLE events MODIFY COLUMN tipo ENUM('avaliacao_nr1','devolutiva','treinamento','palestra','reuniao','outro') NOT NULL DEFAULT 'outro'");
    }
};
