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

    public $incrementing = false;
}
