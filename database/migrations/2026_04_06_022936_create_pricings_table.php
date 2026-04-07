<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('custo_direto', 10, 2)->default(0);
            $table->decimal('custo_indireto', 10, 2)->default(0);
            $table->decimal('margem_lucro', 5, 2)->default(0);
            $table->decimal('preco_venda', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricings');
    }
};
