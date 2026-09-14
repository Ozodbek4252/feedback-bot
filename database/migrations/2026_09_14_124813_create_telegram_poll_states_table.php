<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_poll_states', function (Blueprint $table) {
            $table->id();
            $table->string('bot')->unique();
            $table->unsignedBigInteger('last_update_id')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_poll_states');
    }
};
