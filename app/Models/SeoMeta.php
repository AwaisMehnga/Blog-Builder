<?php

namespace App\Models;

use Core\Model;

class SeoMeta extends Model
{
    protected string $table = 'seo_meta';
    protected string $primaryKey = 'id';
    public bool $timestamps = false;
    protected array $fillable = [
        'entity_type',
        'entity_id',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'canonical_url'
    ];
}