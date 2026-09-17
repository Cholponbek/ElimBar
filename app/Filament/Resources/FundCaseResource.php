<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundCaseResource\Pages;
use App\Models\FundCase;
use App\Support\CasePhotoProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Минимальная форма ведения кейса (тонкий срез, см. README/ARCHITECTURE.md).
 * budget_minor/allocated_minor/disbursed_minor не редактируются напрямую —
 * budget_minor задаётся здесь один раз при заведении кейса (в сомах, форма
 * конвертирует в минорные единицы), allocated/disbursed управляются только
 * триггерами БД (FundCase::booted() это форсирует, см. app/Models/FundCase.php).
 */
class FundCaseResource extends Resource
{
    protected static ?string $model = FundCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Кейсы';

    protected static ?string $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'кейс';

    protected static ?string $pluralModelLabel = 'кейсы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Бенефициар и категория')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options([
                                'medical' => 'Лечение',
                                'winter_food' => 'Зимняя продуктовая помощь',
                                'fund_project' => 'Проект фонда',
                            ])
                            ->helperText('«Проект фонда» — собственный сбор фонда/донора (не про одного конкретного человека), бенефициар не нужен.')
                            ->live()
                            ->required(),
                        // Проект фонда — это не про одного человека (форма для
                        // сирот, помощь малоимущим семьям), поэтому для него
                        // поле скрыто и не обязательно; cases.beneficiary_id
                        // сделан nullable именно под этот случай, см. миграцию
                        // 2026_08_30_120000_make_cases_beneficiary_id_nullable.
                        Forms\Components\Select::make('beneficiary_id')
                            ->label('Бенефициар')
                            ->relationship('beneficiary', 'full_name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('category') !== 'fund_project')
                            ->required(fn (Forms\Get $get) => $get('category') !== 'fund_project')
                            ->dehydrated(fn (Forms\Get $get) => $get('category') !== 'fund_project')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('full_name')->label('ФИО')->required(),
                                Forms\Components\TextInput::make('phone')->label('Телефон')->tel(),
                            ]),
                    ]),

                Forms\Components\Section::make('Публичная карточка')
                    ->description('Видно донорам на витрине — без ФИО и других данных бенефициара.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('public_title.ky')
                            ->label('Заголовок (кыргызча)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('public_title.ru')
                            ->label('Заголовок (русский)')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('public_story.ky')
                            ->label('История (кыргызча)')
                            ->rows(4),
                        Forms\Components\Textarea::make('public_story.ru')
                            ->label('История (русский)')
                            ->rows(4),
                        Forms\Components\FileUpload::make('public_photo_paths')
                            ->label('Фото (карусель)')
                            ->helperText('Обычные публичные картинки, без ограничений доступа — не документы о расходах. Можно загрузить несколько, порядок — как на витрине.')
                            ->disk('public')
                            ->directory('case-photos')
                            ->visibility('public')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            // Ресайз в браузере перед загрузкой (Filament, через
                            // filepond-plugin-image-resize) — карточки на сайте
                            // никогда не показывают фото шире ~800px (aspect-video
                            // в 2/3 колонки), а телефонные камеры отдают 3000–4000px.
                            // upscale(false) — не увеличивает уже маленькие фото.
                            ->imageResizeTargetWidth('1600')
                            ->imageResizeTargetHeight('900')
                            ->imageResizeMode('cover')
                            ->imageResizeUpscale(false)
                            ->maxSize(5120)
                            // Клиентский ресайз выше уменьшает разрешение, но не
                            // формат/сжатие — телефон отдаёт тяжёлый JPEG, после
                            // ресайза в браузере это всё ещё может быть
                            // несколько сотен килобайт на фото. Здесь —
                            // серверный проход через CasePhotoProcessor: то же
                            // разрешение, но гарантированно пережатый JPEG,
                            // независимо от того, что реально прислал браузер.
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): ?string {
                                if (! $file->exists()) {
                                    return null;
                                }

                                try {
                                    $jpeg = CasePhotoProcessor::process($file->get());
                                } catch (Throwable) {
                                    // Не смогли обработать (неподдерживаемый
                                    // формат и т.п.) — сохраняем как есть, это
                                    // лучше, чем остаться совсем без фото.
                                    return $file->store('case-photos', 'public');
                                }

                                $path = 'case-photos/'.Str::ulid().'.jpg';
                                Storage::disk('public')->put($path, $jpeg);

                                return $path;
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Бюджет и статус')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('budget_minor')
                            ->label('Бюджет, сом')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->afterStateHydrated(fn (Forms\Components\TextInput $component, $state) => $component->state($state !== null ? $state / 100 : null))
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'active' => 'Активен',
                                'closed' => 'Закрыт',
                            ])
                            ->default('draft')
                            ->required(),
                        Forms\Components\Toggle::make('allows_zakat')
                            ->label('Принимает закят')
                            ->helperText('Религиозное ограничение — закят нельзя аллоцировать на кейс без этой отметки.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('public_title.ru')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('beneficiary.full_name')
                    ->label('Бенефициар')
                    ->placeholder('— проект фонда —'),
                Tables\Columns\TextColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'medical' => 'Лечение',
                        'winter_food' => 'Зимняя продуктовая помощь',
                        'fund_project' => 'Проект фонда',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'danger' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'closed' => 'Закрыт',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('budget_minor')
                    ->label('Бюджет')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
                Tables\Columns\TextColumn::make('disbursed_minor')
                    ->label('Выплачено')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFundCases::route('/'),
            'create' => Pages\CreateFundCase::route('/create'),
            'edit' => Pages\EditFundCase::route('/{record}/edit'),
        ];
    }
}
