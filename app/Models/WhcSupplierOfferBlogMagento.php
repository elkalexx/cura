<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhcSupplierOfferBlogMagento extends Model
{
    protected $table = 'whc_supplier_offer_blog_magento';

    protected $fillable = [
        'whc_supplier_offer_blog_id',
        'magento_blog_id',
        'status',
        'url_key',
        'created_magento_at',
    ];

    public function whcSupplierOfferBlog(): BelongsTo
    {
        return $this->belongsTo(WhcSupplierOfferBlog::class);
    }

    protected function casts(): array
    {
        return [
            'created_magento_at' => 'datetime',
        ];
    }
}
