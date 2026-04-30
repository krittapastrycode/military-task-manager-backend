<?php

namespace App\Filament\Resources\ProtectionPersonResource\Pages;

use App\Filament\Resources\ProtectionPersonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProtectionPerson extends EditRecord
{
    protected static string $resource = ProtectionPersonResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
