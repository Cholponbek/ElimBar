<?php

namespace App\Filament\Resources\DisbursementResource\Pages;

use App\Filament\Resources\DisbursementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDisbursement extends CreateRecord
{
    protected static string $resource = DisbursementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['disbursed_by'] = auth()->id();
        $data['disbursed_at'] = now();

        return $data;
    }
}
