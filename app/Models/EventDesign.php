<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPublicUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O projeto de uma festa: a ideia antes de virar orçamento.
 *
 * É onde a Sol guarda o tema, as cores, as fotos de referência e o material
 * que tenciona levar — tudo aquilo que hoje vive num caderno ou numa
 * conversa de WhatsApp consigo própria.
 *
 * Chama-se design e não project porque `projects` já é o portefólio, e são
 * coisas opostas: um é o rascunho privado de uma festa que ainda não
 * aconteceu; o outro é a foto da festa que correu bem.
 *
 * O material listado aqui é uma INTENÇÃO, não uma reserva. Nada fica preso
 * até o orçamento ser aceite — é lá que o trigger do stock decide.
 */
class EventDesign extends Model
{
    use HasFactory;
    use HasPublicUuid;

    protected $fillable = [
        'event_id', 'theme', 'palette', 'notes', 'inspiration', 'photos', 'checklist',
    ];

    protected $attributes = [
        'palette' => '[]',
        'inspiration' => '[]',
        'photos' => '[]',
        'checklist' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'palette' => 'array',
            'inspiration' => 'array',
            'photos' => 'array',
            'checklist' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EventDesignItem::class, 'design_id')->orderBy('position');
    }

    /** Quantos passos da montagem já estão feitos, para a barra de progresso. */
    public function checklistDone(): int
    {
        return count(array_filter($this->checklist ?? [], fn ($step) => ! empty($step['done'])));
    }

    public function checklistTotal(): int
    {
        return count($this->checklist ?? []);
    }
}
