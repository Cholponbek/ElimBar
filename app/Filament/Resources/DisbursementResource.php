<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisbursementResource\Pages;
use App\Models\Disbursement;
use App\Models\FundCase;
use App\Models\Proof;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

/**
 * Append-only (см. IsAppendOnly + БД-триггеры disbursements_forbid_*):
 * только создание, без редактирования и удаления. proof_id NOT NULL на
 * уровне схемы (FK) — форма не даёт создать выплату без документа, но
 * граница всё равно в БД, не здесь.
 *
 * disbursed_minor на кейсе пересчитывается триггером apply_disbursement,
 * не этим классом; CHECK cases_disbursed_within_budget в БД откатывает
 * вставку, если сумма выплат пробивает бюджет кейса — форма не дублирует
 * эту проверку заранее, ошибка БД просто всплывёт как ошибка сохранения.
 */
class DisbursementResource extends Resource
{
    protected static ?string $model = Disbursement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?string $navigationLabel = 'Выплаты';

    protected static ?string $navigationGroup = 'Выплаты';

    protected static ?string $modelLabel = 'выплата';

    protected static ?string $pluralModelLabel = 'выплаты';

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('case_id')
                    ->label('Кейс')
                    ->options(fn () => FundCase::query()
                        ->whereIn('status', ['active', 'closed'])
                        ->get()
                        ->mapWithKeys(fn (FundCase $case) => [$case->id => ($case->public_title['ru'] ?? $case->public_title['ky'] ?? '#'.$case->id)]))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('proof_id')
                    ->label('Документ')
                    ->relationship('proof', 'original_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->helperText('Чек, акт или другое подтверждение расхода. Загрузить новый — в разделе «Документы».')
                    ->createOptionForm([
                        Forms\Components\FileUpload::make('path')
                            ->label('Файл')
                            ->disk('proofs')
                            ->directory(fn () => 'proofs/'.now()->format('Y/m'))
                            ->visibility('private')
                            ->required()
                            ->maxSize(10240),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        $disk = Storage::disk('proofs');
                        $path = $data['path'];

                        return Proof::create([
                            'disk' => 'proofs',
                            'path' => $path,
                            'sha256' => hash('sha256', $disk->get($path)),
                            'mime' => $disk->mimeType($path) ?: 'application/octet-stream',
                            'size_bytes' => $disk->size($path),
                            'original_name' => basename($path),
                            'uploaded_by' => auth()->id(),
                        ])->id;
                    }),
                Forms\Components\TextInput::make('amount_minor')
                    ->label('Сумма, сом')
                    ->numeric()
                    ->minValue(0.01)
                    ->required()
                    ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                Forms\Components\Textarea::make('recipient_note')
                    ->label('Заметка о получателе')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('disbursed_at')->label('Дата')->dateTime('d.m.Y H:i'),
                TextEntry::make('case.public_title.ru')->label('Кейс'),
                TextEntry::make('amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state, Disbursement $record) => number_format($state / 100, 0, '.', ' ').' '.$record->currency),
                TextEntry::make('proof.original_name')->label('Документ'),
                TextEntry::make('disbursedBy.name')->label('Провёл'),
                TextEntry::make('recipient_note')->label('Заметка')->default('—')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('disbursed_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('case.public_title.ru')
                    ->label('Кейс')
                    ->limit(40),
                Tables\Columns\TextColumn::make('amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state, Disbursement $record) => number_format($state / 100, 0, '.', ' ').' '.$record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('proof.original_name')
                    ->label('Документ'),
                Tables\Columns\TextColumn::make('disbursedBy.name')
                    ->label('Провёл'),
            ])
            ->defaultSort('disbursed_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('proof')
                    ->label('Документ')
                    ->icon('heroicon-o-paper-clip')
                    ->url(fn (Disbursement $record) => $record->proof->temporaryUrl())
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisbursements::route('/'),
            'create' => Pages\CreateDisbursement::route('/create'),
            'view' => Pages\ViewDisbursement::route('/{record}'),
        ];
    }
}
