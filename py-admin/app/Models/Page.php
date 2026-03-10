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
        'content_draft',
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
        'content_draft' => 'array',
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
     * The main method serving content to the API.
     * Decides whether to send the LIVE or DRAFT version.
     */
    public function getResolvedContent(bool $isPreview = false): ?array 
    {
        if ($isPreview) {
            // In preview mode, the scratchpad takes priority.
            // If the draft is empty, fallback to the original content.
            return $this->content_draft ?? $this->content;
        }

        // For regular users, always only official content.
        return $this->content;
    }

    /**
     * Generates a secure preview link with an HMAC signature.
     */
    public function getPreviewUrl(): string
    {
        $expires = now()->addMinutes(30)->timestamp;
        $path = ($this->full_url && $this->full_url !== '/') ? ltrim($this->full_url, '/') : 'homepage';

        $signature = hash_hmac('sha256', "{$path}|{$expires}", config('app.key'));

        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        return "{$frontendUrl}/api/preview?path={$path}&expires={$expires}&signature={$signature}";
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
