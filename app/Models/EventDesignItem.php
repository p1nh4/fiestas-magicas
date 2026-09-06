<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma peça que o desenho prevê. Intenção, não reserva. */
class EventDesignItem extends Model
{
    use HasFactory;

    protected $fillable = ['design_id', 'item_id', 'quantity', 'notes', 'position'];

    protected $attributes = [
        'quantity' => 1,
        'position' => 0,
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'position' => 'integer'];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(EventDesign::class, 'design_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
