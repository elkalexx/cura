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
        'created_at_whc',
        'updated_at_whc',
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
