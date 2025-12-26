<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $table = 'service_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Sanitize icon on set and expose a safe accessor
    public function setIconAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['icon'] = null;
            return;
        }

        if (function_exists('purify') || class_exists('\Mews\Purifier\Facades\Purifier')) {
            $this->attributes['icon'] = \Mews\Purifier\Facades\Purifier::clean($value);
            return;
        }

        // Fallback: strip tags except safe SVG tags
        $this->attributes['icon'] = strip_tags((string)$value, '<svg><path><circle><rect><line><polyline><polygon><g><title>');
    }

    public function getIconSafeAttribute(): ?string
    {
        return $this->attributes['icon'] ?? null;
    }

    /**
     * Get all plans in this category
     */
    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * Scope: only active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    /**
     * Scope: order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Find category by slug
     */
    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->active()->first();
    }
}
