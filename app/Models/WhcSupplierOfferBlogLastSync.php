<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhcSupplierOfferBlogLastSync extends Model
{
    protected $table = 'whc_supplier_offer_blog_last_sync';

    protected $fillable = [
        'last_synced',
    ];

    protected function casts(): array
    {
        return [
            'last_synced' => 'datetime',
        ];
    }
}
