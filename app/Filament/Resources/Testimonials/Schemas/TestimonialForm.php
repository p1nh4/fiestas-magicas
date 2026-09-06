<?php

declare(strict_types=1);

namespace App\Filament\Resources\Testimonials\Schemas;

use App\Enums\Locale;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lo que dijo')
                    ->description('Cópialo tal cual. Corregir la ortografía está bien; cambiar lo que dice, no.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('author_name')
                            ->label('Quién lo dijo')
                            ->required()
                            ->maxLength(120)
                            ->helperText('El nombre que ella misma usaría. "Marta P." vale.'),
                        Select::make('locale')
                            ->label('Idioma')
                            ->options(Locale::class)
                            ->default(Locale::Es->value)
                            ->required()
                            ->native(false)
                            ->helperText('Se muestra solo a quien vea el sitio en este idioma.'),
                        Textarea::make('body')
                            ->label('Texto')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        TextInput::make('rating')
                            ->label('Estrellas')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->helperText('Opcional.'),
                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('De dónde salió')
                    ->columns(2)
                    ->schema([
                        TextInput::make('source')
                            ->label('Origen')
                            ->maxLength(24)
                            ->datalist(['google', 'instagram', 'facebook', 'email', 'whatsapp'])
                            ->helperText('Dónde lo escribió.'),
                        TextInput::make('source_url')
                            ->label('Enlace')
                            ->url()
                            ->maxLength(400)
                            ->helperText('Si está publicado en algún sitio, el enlace es la prueba.'),
                    ]),

                /*
                | Sem autorização não entra.
                |
                | A coluna e NOT NULL na base de dados, portanto isto nao e
                | uma validacao de formulario que se contorne: e a linha que
                | nao existe. E o projeto tem testes que falham se aparecer
                | uma avaliacao inventada no site.
                */
                Section::make('Permiso')
                    ->description('Sin permiso no se guarda. Pídeselo por WhatsApp y apunta la fecha: es la prueba.')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('consent_at')
                            ->label('Nos dio permiso el')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->required()
                            ->default(now()),
                        Toggle::make('is_published')
                            ->label('Visible en la web'),
                    ]),
            ]);
    }
}
