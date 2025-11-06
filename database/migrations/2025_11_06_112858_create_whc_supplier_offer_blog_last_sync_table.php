<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whc_supplier_offer_blog_last_sync', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_synced');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whc_supplier_offer_blog_last_sync');
    }
};
