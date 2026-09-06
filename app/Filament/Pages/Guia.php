<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * A página de ajuda, dentro do próprio backoffice.
 *
 * Um sistema que só a pessoa que o escreveu sabe usar não está acabado. E
 * um manual em PDF numa pasta qualquer não se lê: lê-se o que está ao lado
 * do botão, no momento em que a dúvida aparece.
 *
 * Só texto — nada de estado, nada de formulários. É de propósito: uma
 * página de ajuda que pode falhar é uma página de ajuda que falha quando
 * mais falta faz.
 */
class Guia extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Ayuda';

    protected static ?string $navigationLabel = 'Cómo se usa esto';

    protected static ?string $title = 'Cómo se usa esto';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.guia';
}
