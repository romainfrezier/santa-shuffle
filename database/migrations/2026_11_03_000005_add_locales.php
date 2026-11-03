<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['exchanges', 'participants', 'deliveries'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('locale', 2)->default('fr'));
        }
    }

    public function down(): void
    {
        foreach (['exchanges', 'participants', 'deliveries'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('locale'));
        }
    }
};
