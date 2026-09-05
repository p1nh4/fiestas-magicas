<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** Quando um slug muda, o SEO nao se perde. */
class Redirect extends Model
{
    use HasFactory;

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits'];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer'];
    }
}
