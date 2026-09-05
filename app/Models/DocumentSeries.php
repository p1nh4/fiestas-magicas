<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Serie de numeracao. Separada por tipo e por ano — e o que a lei
 * espanhola pede a quem fatura.
 */
class DocumentSeries extends Model
{
    use HasFactory;

    protected $table = 'document_series';

    protected $fillable = ['code', 'doc_type', 'prefix', 'year', 'next_sequence', 'is_active'];

    protected function casts(): array
    {
        return [
            'doc_type' => DocumentType::class,
            'year' => 'integer',
            'next_sequence' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'series_id');
    }

    public function formatNumber(int $sequence): string
    {
        return sprintf('%s/%d/%04d', $this->prefix ?: $this->code, $this->year, $sequence);
    }
}
