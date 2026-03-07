<?php

namespace App\Models;

use App\Models\Setting;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'visibility',
        'password',
        'published_at',
        'user_id',
        'parent_id',
    ];

    protected $casts = [
        'content' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
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

    protected static function booted() {
        static::saving(function ($page) {
            $homepageId = Setting::get('homepage_id');

            // If this page has just become/is the home page
            if ($homepageId && (string)$page->id === (string)$homepageId) {
                $page->slug = null;
                $page->parent_id = null;
            }
        });
    }

    /**
     * Calculates the full URL path of a page based on its parent hierarchy.
     * Example: /about-us/team/jan-smith
     */
    protected function fullUrl(): Attribute
    {
        return Attribute::make(
            get: function() {
                // If page have parent, get his full url
                if ($this->parent_id && $this->parent) {
                    return rtrim($this->parent->full_url, '/') . '/' . $this->slug;
                }

                // If page is main return /
                return '/' . $this->slug;
            },
        );
    }

    /**
     * Check if the resource is currently live and visible to users.
     * * The resource is considered "live" if:
     * 1. The status is explicitly set to 'published'.
     * 2. The publication date is either not set (null) or is in the past/present.
     *
     * @return bool
     */
    public function isLive(): bool
    {
        return $this->status === 'published' &&
            $this->visibility !== 'private' &&
            ($this->published_at === null || $this->published_at <= now());
    }
}
