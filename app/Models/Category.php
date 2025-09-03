<?php

namespace App\Models;

use Core\Model;

class Category extends Model
{
    protected string $table = 'categories';
    protected string $primaryKey = 'id';
    public bool $timestamps = true;
    protected array $fillable = [
        'name',
        'slug',
        'description',
        'parent_id'
    ];

    public function generateSlug(): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name)));
        return $slug;
    }

    public static function getParentCategories()
    {
        return static::where('parent_id', null);
    }

    public static function getSubCategories($parentId)
    {
        return static::where('parent_id', $parentId);
    }
}