<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whc_supplier_offer_blogs', function (Blueprint $table) {
            $table->boolean('is_sold')->after('file_path')->nullable();
            $table->string('offer_title')->after('offer_no')->nullable();
            $table->string('supplier')->after('title')->nullable();
            $table->integer('offer_ext_status')->after('supplier')->nullable();
            $table->boolean('is_brand')->after('offer_ext_status')->nullable();
            $table->boolean('is_b_group_appr')->after('is_brand')->nullable();
            $table->boolean('is_approved')->after('is_b_group_appr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whc_supplier_offer_blogs', function (Blueprint $table) {
            //
        });
    }
};
