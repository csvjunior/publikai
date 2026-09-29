<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o campo de função do usuário (admin/operator).
     *
     * Decisão Sprint 0.1: string controlada de 20 posições em vez de
     * enum nativo do banco, para manter compatibilidade entre
     * MariaDB 10.4 (local) e MySQL 8 (produção futura) e facilitar
     * evolução sem alteração destrutiva. A camada PHP valida via
     * App\Enums\UserRole.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('operator')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
