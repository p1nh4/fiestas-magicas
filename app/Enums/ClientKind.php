<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Gerado a partir de database/schema/schema.sql — nao editar a mao.
 *
 * Os valores sao exatamente os que a base de dados aceita. O CHECK do
 * Postgres continua a ser a ultima linha de defesa; este enum e para o
 * PHP e para os formularios.
 */
enum ClientKind: string
{
    case Person = 'person';
    case Company = 'company';

    /** Etiqueta traduzida (lang/{es,gl,pt}/enums.php). */
    public function label(): string
    {
        return __('enums.client_kind.' . $this->value);
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
