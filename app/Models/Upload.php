<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $guarded = [];
    protected $casts = [
        'uploaded_at' => 'datetime',
    ];
}
