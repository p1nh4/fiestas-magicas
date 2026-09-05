<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Faq extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['question', 'answer'];

    protected $fillable = ['question', 'answer', 'position', 'is_published'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'is_published' => 'boolean'];
    }
}
