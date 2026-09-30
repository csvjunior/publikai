<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roteiros falhados não possuem texto (Sprint 5.4): hook/body/cta passam
     * a nullable no banco. Fluxos válidos (manual/revise/ready) continuam
     * exigindo os três via validação e validação de schema da IA.
     */
    public function up(): void
    {
        Schema::table('content_scripts', function (Blueprint $table) {
            $table->text('hook')->nullable()->change();
            $table->text('body')->nullable()->change();
            $table->text('cta')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('content_scripts', function (Blueprint $table) {
            $table->text('hook')->nullable(false)->change();
            $table->text('body')->nullable(false)->change();
            $table->text('cta')->nullable(false)->change();
        });
    }
};
