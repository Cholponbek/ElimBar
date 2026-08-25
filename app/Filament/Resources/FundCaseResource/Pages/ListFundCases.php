<?php

namespace App\Filament\Resources\FundCaseResource\Pages;

use App\Filament\Resources\FundCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFundCases extends ListRecords
{
    protected static string $resource = FundCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
