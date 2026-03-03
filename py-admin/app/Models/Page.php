<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'seo',
        'status',
        'user_id',
        'parent_id',
    ];

    protected $casts = [
        'content' => 'array',
        'seo' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id');
    }

    /**
     * Calculates the full URL path of a page based on its parent hierarchy.
     * Example: /about-us/team/jan-smith
     */
    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: function() {
                $slugs = collect([$this->slug]);
                $current = $this;

                // Recursive tree traversal
                while ($current->parent_id && $parent = $current->parent) {
                    $slugs->prepend($parent->slug);
                    $current = $parent;
                }

                return '/' . $slugs->implode('/');
            },
        );
    }
}
