<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'path',
        'value',
        'secret_value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'secret_value' => 'encrypted:array',
        ];
    }
}
