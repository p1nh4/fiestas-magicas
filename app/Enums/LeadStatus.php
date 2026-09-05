<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Gerado a partir de database/schema/schema.sql — nao editar a mao.
 *
 * Os valores sao exatamente os que a base de dados aceita. O CHECK do
 * Postgres continua a ser a ultima linha de defesa; este enum e para o
 * PHP e para os formularios.
 *
 * Implementa os contratos do Filament para que um `->options(LeadStatus::class)`
 * mostre a etiqueta traduzida e nao o nome do case em ingles.
 */
enum LeadStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';
    case Spam = 'spam';

    /** Etiqueta traduzida (lang/{es,gl,pt}/enums.php). */
    public function label(): string
    {
        return __('enums.lead_status.' . $this->value);
    }

    /** Contrato do Filament — selects, badges e filtros. */
    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted => 'warning',
            self::Quoted => 'primary',
            self::Won => 'success',
            self::Lost => 'danger',
            self::Spam => 'gray',
        };
    }

    /** Para os selects do Filament e dos formularios publicos. */
    public static function options(): array
    {
        return array_column(
            array_map(
                static fn (self $c): array => ['value' => $c->value, 'label' => $c->label()],
                self::cases()
            ),
            'label',
            'value'
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
