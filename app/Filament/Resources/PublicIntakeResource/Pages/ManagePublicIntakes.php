<?php

namespace App\Filament\Resources\PublicIntakeResource\Pages;

use App\Filament\Resources\PublicIntakeResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePublicIntakes extends ManageRecords
{
    protected static string $resource = PublicIntakeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
