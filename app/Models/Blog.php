<?php

namespace App\Models;

use App\Support\StorageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    public const STATUSES = ['draft', 'active', 'published', 'deleted'];

    protected $fillable = [
        'topic',
        'title',
        'slug',
        'selected_intro',
        'content',
        'featured_img_path',
        'middle_img_path',
        'status',
        'ai_metadata',
    ];

    protected $casts = [
        'ai_metadata' => 'array',
    ];

    public function getFeaturedImgPathAttribute($value): ?string
    {
        return $this->resolveImagePath($value);
    }

    public function getMiddleImgPathAttribute($value): ?string
    {
        return $this->resolveImagePath($value);
    }

    private function resolveImagePath(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $normalized = StorageUrl::normalize($value, 'blogs/');

        if ($normalized) {
            return StorageUrl::publicUrl($normalized);
        }

        return $value;
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getFormattedContentAttribute(): string
    {
        $content = trim((string) $this->content);

        if ($content === '') {
            return '';
        }

        if (Str::contains($content, ['<p', '<br', '<h', '<ul', '<ol'])) {
            return $content;
        }

        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];

        $html = collect($lines)
            ->map(fn ($line) => trim($line))
            ->filter()
            ->map(function ($line) {
                if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $matches)) {
                    $level = min(strlen($matches[1]) + 2, 6);
                    return sprintf('<h%d>%s</h%d>', $level, e(trim($matches[2])), $level);
                }

                return '<p>' . e($line) . '</p>';
            })
            ->implode('');

        return $html ?: '<p>' . e($content) . '</p>';
    }
}
