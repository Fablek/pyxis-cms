<?php

namespace App\Models;

use App\Enums\FieldGroupMode;
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
        'mode',
        'is_active',
    ];

    protected $casts = [
        'fields' => 'array',
        'rules' => 'array',
        'mode' => FieldGroupMode::class,
        'is_active' => 'boolean',
    ];
}
