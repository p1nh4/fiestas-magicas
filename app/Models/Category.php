<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CategoryKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory;
    use HasTranslations;

    public array $translatable = ['name', 'slug'];

    protected $fillable = ['kind', 'name', 'slug', 'position', 'is_active'];

    protected function casts(): array
    {
        return [
            'kind' => CategoryKind::class,
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
