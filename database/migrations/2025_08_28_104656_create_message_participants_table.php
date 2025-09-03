<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id');
            $table->foreignId('contact_id');
            $table->string('kind');
            $table->string('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_participants');
    }
};
