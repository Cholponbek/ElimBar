<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestResource\Pages;
use App\Models\CaseRequest;
use App\Models\FundCase;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Заявки от бенефициаров/операторов (три статуса: pending -> verified ->
 * rejected). Одобрение создаёт FundCase отдельным действием ("Одобрить"
 * ниже), а не сменой статуса — кейс либо есть, либо его нет, третьего
 * промежуточного состояния не вводим (см. миграцию create_requests_table).
 */
class RequestResource extends Resource
{
    protected static ?string $model = CaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Заявки';

    protected static ?string $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'заявка';

    protected static ?string $pluralModelLabel = 'заявки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('beneficiary_id')
                    ->label('Бенефициар')
                    ->relationship('beneficiary', 'full_name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('full_name')->label('ФИО')->required(),
                        Forms\Components\TextInput::make('phone')->label('Телефон')->tel(),
                    ]),
                Forms\Components\Select::make('category')
                    ->label('Категория')
                    ->options([
                        'medical' => 'Лечение',
                        'winter_food' => 'Зимняя продуктовая помощь',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Описание')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('requested_amount_minor')
                    ->label('Запрашиваемая сумма, сом')
                    ->numeric()
                    ->minValue(0)
                    ->afterStateHydrated(fn (Forms\Components\TextInput $component, $state) => $component->state($state !== null ? $state / 100 : null))
                    ->dehydrateStateUsing(fn ($state) => $state !== null ? (int) round(((float) $state) * 100) : null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('beneficiary.full_name')
                    ->label('Бенефициар')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'medical' => 'Лечение',
                        'winter_food' => 'Зимняя продуктовая помощь',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('requested_amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 100, 0, '.', ' ').' сом' : '—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending' => 'На рассмотрении',
                        'verified' => 'Одобрена',
                        'rejected' => 'Отклонена',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Подана')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'На рассмотрении',
                        'verified' => 'Одобрена',
                        'rejected' => 'Отклонена',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (CaseRequest $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CaseRequest $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('public_title_ky')
                            ->label('Заголовок для витрины (кыргызча)')
                            ->required(),
                        Forms\Components\TextInput::make('public_title_ru')
                            ->label('Заголовок для витрины (русский)')
                            ->required(),
                        Forms\Components\TextInput::make('budget_minor')
                            ->label('Утверждённый бюджет, сом')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\Toggle::make('allows_zakat')
                            ->label('Принимает закят'),
                    ])
                    ->action(function (CaseRequest $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $case = FundCase::create([
                                'request_id' => $record->id,
                                'beneficiary_id' => $record->beneficiary_id,
                                'category' => $record->category,
                                'status' => 'active',
                                'public_title' => [
                                    'ky' => $data['public_title_ky'],
                                    'ru' => $data['public_title_ru'],
                                ],
                                'currency' => $record->currency,
                                'budget_minor' => (int) round(((float) $data['budget_minor']) * 100),
                                'allows_zakat' => $data['allows_zakat'] ?? false,
                            ]);

                            $record->update([
                                'status' => 'verified',
                                'verified_by' => auth()->id(),
                                'verified_at' => now(),
                            ]);
                        });
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (CaseRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (CaseRequest $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'verified_by' => auth()->id(),
                            'verified_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}
