<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_codes', function (Blueprint $table) {
            $table->string('email_hash', 64)->primary();
            $table->string('challenge', 64);
            $table->string('code_hash', 64);
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes');
    }
};
