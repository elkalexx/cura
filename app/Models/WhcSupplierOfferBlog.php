<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhcSupplierOfferBlog extends Model
{
    protected $fillable = [
        'offer_no',
        'title',
        'status',
        'description',
        'has_file',
        'file_name',
        'file_path',
        'created_at_whc',
        'updated_at_whc',
        'is_sold',
        'offer_title',
        'supplier',
        'offer_ext_status',
        'is_brand',
        'is_b_group_appr',
        'is_approved',
    ];

    public function whcSupplierOfferBlogMagento(): HasOne
    {
        return $this->hasOne(WhcSupplierOfferBlogMagento::class);
    }

    protected function casts(): array
    {
        return [
            'created_at_whc' => 'datetime',
            'updated_at_whc' => 'datetime',
        ];
    }
}
