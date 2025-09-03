<?php

namespace App\Models;

use Core\Model;

class Blog extends Model
{
    protected string $table = 'blogs';
    protected string $primaryKey = 'id';
    public bool $timestamps = true;
    protected array $fillable = [
        'category_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at'
    ];

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function generateSlug(): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->title)));
        return $slug;
    }

    public static function published()
    {
        return static::where('status', 'published');
    }

    public static function drafts()
    {
        return static::where('status', 'draft');
    }
}