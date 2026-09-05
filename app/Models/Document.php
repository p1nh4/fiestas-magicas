<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentType;
use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Documento emitido.
 *
 * Depois de selado (`locked_at`), a BASE DE DADOS recusa qualquer UPDATE
 * ou DELETE — ver o trigger documents_immutability(). Os campos
 * content_hash / previous_hash formam a cadeia exigida pelo Veri*factu,
 * obrigatorio para autonomos a partir de 1 de julho de 2027.
 */
class Document extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'series_id', 'sequence', 'number', 'doc_type', 'event_id', 'client_id',
        'quote_id', 'issued_at', 'currency', 'subtotal', 'tax_rate', 'tax_amount',
        'total', 'snapshot', 'pdf_path', 'chain_index', 'content_hash',
        'previous_hash', 'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'doc_type' => DocumentType::class,
            'issued_at' => 'immutable_datetime',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'snapshot' => 'array',
            'sequence' => 'integer',
            'chain_index' => 'integer',
            'locked_at' => 'immutable_datetime',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(DocumentSeries::class, 'series_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isSealed(): bool
    {
        return $this->locked_at !== null;
    }

    /**
     * Impressao digital do conteudo, encadeada com a do documento anterior
     * da mesma serie. Alterar um documento antigo passa a ser detetavel:
     * a cadeia deixa de fechar.
     */
    public function computeHash(?string $previousHash): string
    {
        return hash('sha256', implode('|', [
            $this->number,
            $this->doc_type instanceof DocumentType ? $this->doc_type->value : (string) $this->doc_type,
            $this->issued_at?->toIso8601String() ?? '',
            (string) $this->total,
            (string) $this->tax_amount,
            json_encode($this->snapshot ?? [], JSON_THROW_ON_ERROR),
            $previousHash ?? '',
        ]));
    }

    protected static function booted(): void
    {
        // rede a mais, de proposito: a base de dados ja recusa, mas assim o
        // erro aparece no PHP com uma mensagem que se percebe
        static::updating(function (self $doc): void {
            if ($doc->getOriginal('locked_at') !== null) {
                throw new RuntimeException(
                    "O documento {$doc->number} está selado e não pode ser alterado."
                );
            }
        });

        static::deleting(function (self $doc): void {
            if ($doc->locked_at !== null) {
                throw new RuntimeException(
                    "O documento {$doc->number} está selado e não pode ser apagado."
                );
            }
        });
    }
}
