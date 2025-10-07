<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchCategory extends Model
{
    protected $table = 'match_categories';
    protected $fillable = [
        'id',
        'venue_id',
        'name'
    ];

    public $incrementing = false;
}
