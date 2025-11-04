<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whc_supplier_offer_blog_magento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whc_supplier_offer_blog_id');
            $table->integer('magento_blog_id');
            $table->integer('status');
            $table->string('url_key');
            $table->timestamp('created_magento_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whc_supplier_offer_blog_magento');
    }
};
