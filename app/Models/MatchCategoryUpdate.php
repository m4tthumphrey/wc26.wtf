<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchCategoryUpdate extends Model
{
    protected $table = 'match_category_updates';
    protected $fillable = [
        'id',
        'match_id',
        'category_id',
        'price_min',
        'price_max'
    ];

    public $timestamps = ['created_at'];
    const UPDATED_AT = null;
}
