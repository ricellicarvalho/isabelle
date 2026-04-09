<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Converte caminho_arquivo (string) em JSON para suportar múltiplos arquivos.
        // Faz backfill: cada valor existente vira um array com um único elemento.
        Schema::table('client_documents', function (Blueprint $table) {
            $table->json('caminho_arquivo_json')->nullable()->after('caminho_arquivo');
        });

        DB::table('client_documents')->orderBy('id')->each(function ($row) {
            DB::table('client_documents')
                ->where('id', $row->id)
                ->update([
                    'caminho_arquivo_json' => json_encode($row->caminho_arquivo ? [$row->caminho_arquivo] : []),
                ]);
        });

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn('caminho_arquivo');
        });

        Schema::table('client_documents', function (Blueprint $table) {
            $table->renameColumn('caminho_arquivo_json', 'caminho_arquivo');
        });
    }

    public function down(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->string('caminho_arquivo_str')->nullable()->after('caminho_arquivo');
        });

        DB::table('client_documents')->orderBy('id')->each(function ($row) {
            $arr = json_decode($row->caminho_arquivo, true) ?: [];
            DB::table('client_documents')
                ->where('id', $row->id)
                ->update(['caminho_arquivo_str' => $arr[0] ?? '']);
        });

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn('caminho_arquivo');
        });

        Schema::table('client_documents', function (Blueprint $table) {
            $table->renameColumn('caminho_arquivo_str', 'caminho_arquivo');
        });
    }
};
