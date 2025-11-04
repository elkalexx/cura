<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whc_supplier_offer_blogs', function (Blueprint $table) {
            $table->id();
            $table->integer('offer_no');
            $table->text('title');
            $table->integer('status');
            $table->longText('description');
            $table->boolean('has_file');
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('created_at_whc');
            $table->timestamp('updated_at_whc');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whc_supplier_offer_blogs');
    }
};
