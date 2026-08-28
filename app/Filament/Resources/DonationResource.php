<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DonationResource\Pages;
use App\Models\Donation;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Только просмотр. Donation — append-only (см. IsAppendOnly + БД-триггер
 * forbid_donation_money_update): создаются публичной формой (контур A,
 * DonationController), исправление — новая строка-сторно, не отсюда.
 * Сотрудники видят входящие донаты, но не редактируют и не удаляют их.
 */
class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Донаты';

    protected static ?string $navigationGroup = 'Донаты';

    protected static ?string $modelLabel = 'донат';

    protected static ?string $pluralModelLabel = 'донаты';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('paid_at')->label('Дата')->dateTime('d.m.Y H:i'),
                TextEntry::make('donor.phone')->label('Донор'),
                TextEntry::make('case.public_title.ru')->label('Кейс')->default('—'),
                TextEntry::make('amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state, Donation $record) => number_format($state / 100, 0, '.', ' ').' '.$record->currency),
                TextEntry::make('provider_fee_minor')
                    ->label('Комиссия провайдера')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 100, 0, '.', ' ').' сом' : '—'),
                TextEntry::make('status')->label('Статус'),
                TextEntry::make('provider')->label('Провайдер'),
                TextEntry::make('provider_ref')->label('Референс провайдера'),
                TextEntry::make('fund_type')->label('Тип фонда'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('donor.phone')
                    ->label('Донор')
                    ->searchable(),
                Tables\Columns\TextColumn::make('case.public_title.ru')
                    ->label('Кейс')
                    ->limit(40)
                    ->default('—'),
                Tables\Columns\TextColumn::make('amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (int $state, Donation $record) => number_format($state / 100, 0, '.', ' ').' '.$record->currency)
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'completed' => 'Проведён',
                        'pending' => 'Ожидает',
                        'failed' => 'Ошибка',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Провайдер')
                    ->formatStateUsing(fn (string $state) => $state === 'fake' ? 'Демо (без реального списания)' : $state),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'completed' => 'Проведён',
                        'pending' => 'Ожидает',
                        'failed' => 'Ошибка',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDonations::route('/'),
            'view' => Pages\ViewDonation::route('/{record}'),
        ];
    }
}
