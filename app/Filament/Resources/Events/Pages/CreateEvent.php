<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Support\Events\EventReference;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    /**
     * A referência é gerada aqui e não no formulário porque tem de ser
     * gerada dentro da mesma transação que grava o evento — ver
     * EventReference. Um campo no ecrã seria só uma sugestão.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            $data['reference'] = app(EventReference::class)->next(
                isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : null
            );

            return static::getModel()::create($data);
        });
    }
}
