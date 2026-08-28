<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProofResource\Pages;
use App\Models\Proof;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Подтверждающие документы (чеки, акты) для выплат. Приватный диск
 * 'proofs' (MinIO/S3, см. config/filesystems.php) — доступ только через
 * temporaryUrl(), публичной ссылки нет и не будет.
 *
 * Не редактируется и не удаляется после загрузки: выплата ссылается на
 * proof_id по FK (restrictOnDelete), подменить документ задним числом —
 * значит подменить основание уже проведённой выплаты.
 */
class ProofResource extends Resource
{
    protected static ?string $model = Proof::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?string $navigationLabel = 'Документы';

    protected static ?string $navigationGroup = 'Выплаты';

    protected static ?string $modelLabel = 'документ';

    protected static ?string $pluralModelLabel = 'документы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('Файл')
                    ->disk('proofs')
                    ->directory(fn () => 'proofs/'.now()->format('Y/m'))
                    ->visibility('private')
                    ->required()
                    ->maxSize(10240)
                    ->preserveFilenames()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('original_name')
                    ->label('Файл')
                    ->searchable(),
                Tables\Columns\TextColumn::make('size_bytes')
                    ->label('Размер')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 1024, 0, '.', ' ').' КБ' : '—'),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label('Загрузил'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Загружен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Открыть')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Proof $record) => $record->temporaryUrl())
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProofs::route('/'),
        ];
    }
}
