<?php

namespace App\Models;

use Database\Factories\PluginFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plugin extends Model
{
    /** @use HasFactory<PluginFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'version',
        'description',
        'author',
        'is_active',
        'error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
