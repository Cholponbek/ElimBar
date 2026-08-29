<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicIntakeResource\Pages;
use App\Models\Beneficiary;
use App\Models\CaseRequest;
use App\Models\PublicIntake;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Заявки, поданные напрямую с сайта (форма /help, контур A, роль
 * app_public — см. PublicIntakeController). Ещё не Beneficiary/CaseRequest:
 * "Конвертировать" создаёт настоящего бенефициара (или переиспользует
 * существующего) и CaseRequest со статусом pending — дальше она идёт
 * обычным путём через раздел «Заявки» (RequestResource: одобрить/отклонить).
 * Здесь нет отдельного шага одобрения кейса — только "это похоже на
 * реальную заявку" vs "отклонить" (спам, дубликат, недостаточно данных).
 */
class PublicIntakeResource extends Resource
{
    protected static ?string $model = PublicIntake::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'Заявки с сайта';

    protected static ?string $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'заявка с сайта';

    protected static ?string $pluralModelLabel = 'заявки с сайта';

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

    private static function categoryLabel(string $category): string
    {
        return match ($category) {
            'medical' => 'Лечение',
            'winter_food' => 'Зимняя продуктовая помощь',
            default => $category,
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('ФИО')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn (string $state) => self::categoryLabel($state)),
                Tables\Columns\TextColumn::make('description')
                    ->label('Описание')
                    ->limit(60),
                Tables\Columns\TextColumn::make('requested_amount_minor')
                    ->label('Сумма')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state / 100, 0, '.', ' ').' сом' : '—'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'new',
                        'success' => 'converted',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'new' => 'Новая',
                        'converted' => 'Конвертирована',
                        'rejected' => 'Отклонена',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Подана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'new' => 'Новая',
                        'converted' => 'Конвертирована',
                        'rejected' => 'Отклонена',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('convert')
                    ->label('Конвертировать')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (PublicIntake $record) => $record->status === 'new')
                    ->form([
                        Forms\Components\Select::make('beneficiary_id')
                            ->label('Бенефициар')
                            ->helperText('Выберите существующего или создайте нового по данным заявки.')
                            ->options(fn () => Beneficiary::query()->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('full_name')->label('ФИО')->required(),
                                Forms\Components\TextInput::make('phone')->label('Телефон')->tel(),
                            ])
                            ->createOptionUsing(fn (array $data): int => Beneficiary::create($data)->id)
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'medical' => 'Лечение',
                                'winter_food' => 'Зимняя продуктовая помощь',
                            ])
                            ->default(fn (PublicIntake $record) => $record->category)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->default(fn (PublicIntake $record) => $record->description)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('requested_amount')
                            ->label('Запрашиваемая сумма, сом')
                            ->numeric()
                            ->minValue(0)
                            ->default(fn (PublicIntake $record) => $record->requested_amount_minor ? $record->requested_amount_minor / 100 : null),
                    ])
                    ->action(function (PublicIntake $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $request = CaseRequest::create([
                                'beneficiary_id' => $data['beneficiary_id'],
                                'category' => $data['category'],
                                'status' => 'pending',
                                'description' => $data['description'],
                                'requested_amount_minor' => isset($data['requested_amount'])
                                    ? (int) round(((float) $data['requested_amount']) * 100)
                                    : null,
                                'currency' => $record->currency,
                            ]);

                            $record->update([
                                'status' => 'converted',
                                'converted_request_id' => $request->id,
                                'reviewed_by' => auth()->id(),
                                'reviewed_at' => now(),
                            ]);
                        });
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PublicIntake $record) => $record->status === 'new')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Причина отказа')
                            ->required(),
                    ])
                    ->action(function (PublicIntake $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
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
            'index' => Pages\ManagePublicIntakes::route('/'),
        ];
    }
}
