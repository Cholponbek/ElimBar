<?php

namespace App\Filament\Resources\CaseClosureResource\Pages;

use App\Filament\Resources\CaseClosureResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCaseClosure extends ViewRecord
{
    protected static string $resource = CaseClosureResource::class;

    protected static ?string $title = 'Отчёт по закрытию кейса';
}
