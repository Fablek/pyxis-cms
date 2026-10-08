<?php

namespace App\Models;

use Database\Factories\FieldGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldGroup extends Model
{
    /** @use HasFactory<FieldGroupFactory> */
    use HasFactory, HasUuids;

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
