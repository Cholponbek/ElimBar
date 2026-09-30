<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Models\Beneficiary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

/**
 * Приватная сущность контура B. Не выводится и не должна выводиться нигде
 * в контуре A — та роль (app_public) физически не имеет прав на эту
 * таблицу (см. ARCHITECTURE.md §5).
 */
class BeneficiaryResource extends Resource
{
    protected static ?string $model = Beneficiary::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Бенефициары';

    protected static ?string $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'бенефициар';

    protected static ?string $pluralModelLabel = 'бенефициары';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('full_name')
                    ->label('ФИО')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\Select::make('city')
                    ->label('Город')
                    ->options(['Бишкек' => 'Бишкек', 'Ош' => 'Ош'])
                    ->native(false),
                Forms\Components\Textarea::make('notes')
                    ->label('Заметки')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('ФИО')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Телефон'),
                Tables\Columns\TextColumn::make('city')->label('Город'),
                Tables\Columns\TextColumn::make('created_at')->label('Создан')->dateTime('d.m.Y')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->action(function (Beneficiary $record) {
                        try {
                            $record->delete();
                        } catch (QueryException $e) {
                            static::notifyIfRestricted($e);

                            return;
                        }

                        Notification::make()
                            ->title('Бенефициар удалён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $failed = 0;

                            foreach ($records as $record) {
                                try {
                                    $record->delete();
                                } catch (QueryException $e) {
                                    if ($e->getCode() !== '23503') {
                                        throw $e;
                                    }

                                    $failed++;
                                }
                            }

                            $deleted = $records->count() - $failed;

                            if ($deleted > 0) {
                                Notification::make()
                                    ->title("Удалено: {$deleted}")
                                    ->success()
                                    ->send();
                            }

                            if ($failed > 0) {
                                Notification::make()
                                    ->title("Не удалось удалить: {$failed}")
                                    ->body('У них есть связанные кейсы или заявки — сначала переназначьте или удалите эти записи.')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBeneficiaries::route('/'),
        ];
    }

    /**
     * cases/requests/consents.beneficiary_id — restrictOnDelete() в БД
     * (нельзя терять привязку кейса к бенефициару). Без этого перехвата
     * попытка удалить бенефициара с кейсами падает голой 500-й — здесь
     * превращаем именно нарушение foreign key (SQLSTATE 23503) в понятное
     * уведомление, а не глушим все возможные ошибки БД подряд.
     */
    private static function notifyIfRestricted(QueryException $e): void
    {
        if ($e->getCode() !== '23503') {
            throw $e;
        }

        Notification::make()
            ->title('Нельзя удалить бенефициара')
            ->body('У него есть связанные кейсы или заявки — сначала переназначьте или удалите эти записи.')
            ->danger()
            ->send();
    }
}
