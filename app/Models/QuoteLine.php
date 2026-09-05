<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A descricao e o preco ficam congelados na linha. Se o preco do catalogo
 * mudar amanha, o orcamento de hoje continua a dizer o que dizia.
 */
class QuoteLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'service_id', 'item_id', 'description',
        'quantity', 'days', 'unit_price', 'line_total', 'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'days' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /** bcmath e nao float: dinheiro nao se soma em virgula flutuante. */
    public function computeTotal(): string
    {
        return bcmul(
            bcmul((string) $this->quantity, (string) $this->unit_price, 4),
            (string) max(1, (int) $this->days),
            2
        );
    }
}
