<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchObject extends Model
{
    protected $table = 'matches';
    protected $fillable = [
        'id',
        'venue_id',
        'venue_name',
        'date',
        'code',
        'stage'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d'
    ];

    public $incrementing = false;
}
