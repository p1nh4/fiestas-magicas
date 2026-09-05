<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['email', 'locale', 'token', 'confirmed_at', 'unsubscribed_at', 'created_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'locale' => Locale::class,
            'confirmed_at' => 'immutable_datetime',
            'unsubscribed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** Double opt-in: so quem confirmou e nao saiu recebe email. */
    public function scopeMailable(Builder $query): Builder
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }
}
