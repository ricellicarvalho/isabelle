<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('telefones')->nullable()->after('contato_telefone');
        });

        // Migra dados existentes de telefone/contato_telefone para o JSON
        DB::table('clients')->orderBy('id')->each(function ($row) {
            $telefones = [];

            if (filled($row->telefone)) {
                $telefones[] = [
                    'tipo' => 'fixo',
                    'numero' => preg_replace('/\D/', '', $row->telefone),
                ];
            }

            if (filled($row->contato_telefone)) {
                $telefones[] = [
                    'tipo' => 'recado',
                    'numero' => preg_replace('/\D/', '', $row->contato_telefone),
                ];
            }

            if (! empty($telefones)) {
                DB::table('clients')
                    ->where('id', $row->id)
                    ->update(['telefones' => json_encode($telefones)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('telefones');
        });
    }
};
