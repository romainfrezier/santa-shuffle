<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('organizer_email');
            $table->string('access_hash', 64)->unique();
            $table->date('gift_date');
            $table->unsignedInteger('budget');
            $table->text('message')->nullable();
            $table->timestamp('drawn_at')->nullable();
            $table->timestamps();
        });
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('email');
            $table->text('wishes')->nullable();
            $table->string('access_hash', 64)->unique();
            $table->text('access_token');
            $table->foreignId('recipient_id')->nullable()->constrained('participants')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('revealed_at')->nullable();
            $table->timestamps();
            $table->unique(['exchange_id', 'email']);
            $table->unique(['exchange_id', 'recipient_id']);
        });
        Schema::create('exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exchange_id')->constrained()->cascadeOnDelete();
            $table->foreignId('giver_id')->constrained('participants')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('participants')->cascadeOnDelete();
            $table->unique(['giver_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusions');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('exchanges');
    }
};
