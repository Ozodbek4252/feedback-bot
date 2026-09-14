<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_messages', function (Blueprint $table) {
            $table->id();
            $table->string('bot');
            $table->unsignedBigInteger('telegram_message_id');
            $table->unsignedBigInteger('telegram_chat_id');
            $table->unsignedBigInteger('telegram_user_id');
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('telegram_last_name')->nullable();
            $table->string('type')->default('text');
            $table->text('text')->nullable();
            $table->json('payload');
            $table->unsignedBigInteger('forwarded_message_id')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamps();

            $table->unique(['bot', 'telegram_message_id']);
            $table->index(['bot', 'telegram_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_messages');
    }
};
