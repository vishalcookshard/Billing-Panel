<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'is_published',
        'meta_description',
        'meta_keywords',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Sanitize content when saved (HTML Purifier if available)
    public function setContentAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['content'] = null;
            return;
        }

        if (class_exists('\Mews\Purifier\Facades\Purifier')) {
            $this->attributes['content'] = \Mews\Purifier\Facades\Purifier::clean($value);
            return;
        }

        // Fallback: allow a safe subset of tags
        $this->attributes['content'] = strip_tags((string)$value, '<p><a><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><img>');
    }

    /**
     * Get the user who created this page
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this page
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: only published pages
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Find page by slug
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->published()->first();
    }
}
