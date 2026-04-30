<?php

namespace App\Filament\Resources\ProtectionPersonResource\Pages;

use App\Filament\Resources\ProtectionPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProtectionPersons extends ListRecords
{
    protected static string $resource = ProtectionPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
