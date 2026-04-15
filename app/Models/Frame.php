<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Frame extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'name',
        'image_path',
        'coordinates',
        'is_active'
    ];


    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean'
    ];

}
