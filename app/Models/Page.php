<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['title', 'slug', 'body'];

    protected $fillable = ['key', 'title', 'slug', 'body', 'seo', 'is_published'];

    protected function casts(): array
    {
        return ['seo' => 'array', 'is_published' => 'boolean'];
    }
}
