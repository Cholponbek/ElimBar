<?php

namespace App\Filament\Resources\CaseClosureResource\Pages;

use App\Filament\Resources\CaseClosureResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCaseClosure extends EditRecord
{
    protected static string $resource = CaseClosureResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] === 'closed' && $this->record->closed_at === null) {
            $data['closed_at'] = now();
        } elseif ($data['status'] !== 'closed') {
            $data['closed_at'] = null;
        }

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('report')
                ->label('Отчёт')
                ->color('gray')
                ->url(fn () => CaseClosureResource::getUrl('view', ['record' => $this->record])),
            $this->getCancelFormAction(),
        ];
    }
}
