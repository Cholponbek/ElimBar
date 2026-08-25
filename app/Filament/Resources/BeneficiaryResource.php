<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BeneficiaryResource\Pages;
use App\Models\Beneficiary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBeneficiaries::route('/'),
        ];
    }
}
