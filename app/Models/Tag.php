<?php

namespace App\Models;

use Core\Model;

class Tag extends Model
{
    protected string $table = 'tags';
    protected string $primaryKey = 'id';
    public bool $timestamps = false;
    protected array $fillable = [
        'name',
        'slug'
    ];

    public function generateSlug(): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $this->name)));
        return $slug;
    }
}