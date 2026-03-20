<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DoctorCommand extends Command
{
    protected $signature = 'doctor';
    protected $description = 'Verifica a saúde da aplicação';

    public function handle()
    {
        $this->info("🔍 Iniciando diagnóstico...\n");

        // 1. PHP vs DB Time
        try {
            $phpTime = now()->toDateTimeString();
            $dbTime = DB::select('SELECT NOW() as agora')[0]->agora;

            $this->line("🕒 PHP: $phpTime");
            $this->line("🕒 DB : $dbTime");
        } catch (\Exception $e) {
            $this->error("❌ Erro ao verificar horário: " . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Timezone
        $this->line("🌎 Timezone Laravel: " . config('app.timezone'));
        $this->line("🌎 Timezone PHP: " . date_default_timezone_get());

        // 3. Conexão DB
        try {
            DB::connection()->getPdo();
            $this->info("✅ Banco conectado");
        } catch (\Exception $e) {
            $this->error("❌ Erro banco: " . $e->getMessage());
            return Command::FAILURE;
        }

        // 4. Query simples
        try {
            DB::select('SELECT 1');
            $this->info("✅ Query OK");
        } catch (\Exception $e) {
            $this->error("❌ Query falhou");
            return Command::FAILURE;
        }

        // 5. Extensões
        $this->line("📦 PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'OK' : 'FALHA'));
        $this->line("📦 mbstring: " . (extension_loaded('mbstring') ? 'OK' : 'FALHA'));
        $this->line("📦 intl: " . (extension_loaded('intl') ? 'OK' : 'FALHA'));

        // 6. Locale
        setlocale(LC_TIME, 'pt_BR.UTF-8');
        $this->line("🌍 Locale: " . strftime('%A, %d %B %Y'));

        // 7. Encoding
        $this->line("🔤 Encoding: " . mb_internal_encoding());

        // 8. Hash
        $this->line("🔐 Hash: " . Hash::make('123456'));

        // 9. Storage
        try {
            Storage::put('doctor.txt', 'ok');
            $this->info("✅ Storage OK");
        } catch (\Exception $e) {
            $this->error("❌ Storage falhou");
            return Command::FAILURE;
        }

        // 10. ENV
        $this->line("⚙️ APP_ENV: " . env('APP_ENV'));
        $this->line("⚙️ DB_HOST: " . env('DB_HOST'));

        // 11. Rede Docker
        $this->line("🌐 DB Host IP: " . gethostbyname(env('DB_HOST')));

        $this->info("\n🚀 Diagnóstico finalizado!");

        return Command::SUCCESS;
    }
}