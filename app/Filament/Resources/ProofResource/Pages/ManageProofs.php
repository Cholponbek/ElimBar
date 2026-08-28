<?php

namespace App\Filament\Resources\ProofResource\Pages;

use App\Filament\Resources\ProofResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Storage;

class ManageProofs extends ManageRecords
{
    protected static string $resource = ProofResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // FileUpload's ->getState() only gives us the stored
                    // path — the rest of Proof's columns (hash, mime,
                    // size, original name) come from reading the file
                    // back off the disk it was just stored to.
                    $disk = Storage::disk('proofs');
                    $path = $data['path'];

                    $data['disk'] = 'proofs';
                    $data['sha256'] = hash('sha256', $disk->get($path));
                    $data['mime'] = $disk->mimeType($path) ?: 'application/octet-stream';
                    $data['size_bytes'] = $disk->size($path);
                    $data['original_name'] = basename($path);
                    $data['uploaded_by'] = auth()->id();

                    return $data;
                }),
        ];
    }
}
