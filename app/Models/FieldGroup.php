<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FieldGroup extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'fields',
        'rules',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'rules' => 'array',
        'is_active' => 'boolean',
    ];
}
