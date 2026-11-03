<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exchanges', function (Blueprint $t) {
            $t->text('access_token')->nullable();
            $t->unsignedInteger('access_version')->default(1);
            $t->unsignedInteger('draw_version')->default(0);
            $t->timestamp('cancelled_at')->nullable();
        });
        Schema::table('participants', fn (Blueprint $t) => $t->unsignedInteger('access_version')->default(1));
        Schema::create('deliveries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('exchange_id')->constrained()->cascadeOnDelete();
            $t->foreignId('participant_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('kind', 20);
            $t->unsignedInteger('access_version');
            $t->unsignedInteger('draw_version');
            $t->string('status', 20)->default('pending');
            $t->timestamp('accepted_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::table('participants', fn (Blueprint $t) => $t->dropColumn('access_version'));
        Schema::table('exchanges', fn (Blueprint $t) => $t->dropColumn(['access_token', 'access_version', 'draw_version', 'cancelled_at']));
    }
};
