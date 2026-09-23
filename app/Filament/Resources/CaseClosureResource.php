<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseClosureResource\Pages;
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
 * Отдельная вкладка «Закрытие кейса» — тот же кейс (FundCase), что и в
 * FundCaseResource, но форма сфокусирована на закрытии: сроки, финансовые/
 * юридические документы, отчёт о проведённых мероприятиях. Кейсы здесь не
 * создаются и не удаляются — только список существующих и переход в форму
 * закрытия конкретного кейса.
 */
class CaseClosureResource extends Resource
{
    protected static ?string $model = FundCase::class;

    protected static ?string $slug = 'case-closures';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Закрытие кейса';

    protected static ?string $navigationGroup = 'Кейсы';

    protected static ?string $modelLabel = 'закрытие кейса';

    protected static ?string $pluralModelLabel = 'закрытие кейсов';

    public static function canCreate(): bool
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
                Forms\Components\Section::make('Кейс')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('public_title_display')
                            ->label('Наименование')
                            ->content(fn (?FundCase $record) => $record?->public_title['ru'] ?? $record?->public_title['ky'] ?? '—'),
                        Forms\Components\Placeholder::make('public_story_display')
                            ->label('Краткое описание')
                            ->content(fn (?FundCase $record) => Str::limit(strip_tags($record?->public_story['ru'] ?? $record?->public_story['ky'] ?? ''), 200, '…') ?: '—'),
                        Forms\Components\Placeholder::make('budget_display')
                            ->label('Нужно было собрать')
                            ->content(fn (?FundCase $record) => number_format(($record?->budget_minor ?? 0) / 100, 0, '.', ' ').' сом'),
                        Forms\Components\Placeholder::make('collected_display')
                            ->label('Собрано')
                            ->content(fn (?FundCase $record) => number_format(($record?->allocated_minor ?? 0) / 100, 0, '.', ' ').' сом'),
                    ]),

                Forms\Components\Section::make('Сроки кейса')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Дата начала')
                            ->required(),
                        Forms\Components\Toggle::make('is_indefinite')
                            ->label('Бессрочный')
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Forms\Components\Toggle $component, ?FundCase $record) => $component->state($record?->end_date === null && $record?->exists)),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Дата окончания')
                            ->helperText('Оставьте пустым или включите «Бессрочный», если дата окончания ещё не определена.')
                            ->visible(fn (Forms\Get $get) => ! $get('is_indefinite'))
                            ->dehydratedWhenHidden()
                            ->dehydrateStateUsing(fn (Forms\Get $get, $state) => $get('is_indefinite') ? null : $state)
                            ->afterOrEqual('start_date'),
                    ]),

                Forms\Components\Section::make('Статус')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'active' => 'Активен',
                                'closed' => 'Закрыт',
                            ])
                            ->helperText('При переводе в «Закрыт» дата закрытия проставится автоматически.')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Финансовые и юридические документы')
                    ->description('Чеки, акты, отчёты и другие подтверждающие документы — PDF или JPG.')
                    ->schema([
                        Forms\Components\FileUpload::make('financial_documents_paths')
                            ->label('Документы')
                            ->disk('proofs')
                            ->directory(fn (?FundCase $record) => 'case-closures/'.($record?->id ?? 'new').'/documents')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg'])
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Отчёт по кейсу')
                    ->description('Проведённые мероприятия по кейсу.')
                    ->schema([
                        Forms\Components\Textarea::make('closure_report_description.ky')
                            ->label('Описание (кыргызча)')
                            ->rows(4),
                        Forms\Components\Textarea::make('closure_report_description.ru')
                            ->label('Описание (русский)')
                            ->rows(4),
                        Forms\Components\FileUpload::make('closure_report_photo_paths')
                            ->label('Фото отчёта')
                            ->helperText('Можно загрузить несколько фото — серверный проход через CasePhotoProcessor пережимает их без заметной потери качества.')
                            ->disk('public')
                            ->directory('case-closure-photos')
                            ->visibility('public')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->appendFiles()
                            ->imageResizeTargetWidth('1600')
                            ->imageResizeTargetHeight('1600')
                            ->imageResizeMode('contain')
                            ->imageResizeUpscale(false)
                            ->maxSize(5120)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): ?string {
                                if (! $file->exists()) {
                                    return null;
                                }

                                try {
                                    $jpeg = CasePhotoProcessor::process($file->get());
                                } catch (Throwable) {
                                    return $file->store('case-closure-photos', 'public');
                                }

                                $path = 'case-closure-photos/'.Str::ulid().'.jpg';
                                Storage::disk('public')->put($path, $jpeg);

                                return $path;
                            })
                            ->columnSpanFull(),
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
                    ->label('Нужно было')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
                Tables\Columns\TextColumn::make('allocated_minor')
                    ->label('Собрано')
                    ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Начало')
                    ->date('d.m.Y')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Конец')
                    ->date('d.m.Y')
                    ->placeholder('Бессрочный'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'active' => 'Активен',
                        'closed' => 'Закрыт',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Закрытие кейса'),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaseClosures::route('/'),
            'edit' => Pages\EditCaseClosure::route('/{record}/edit'),
        ];
    }
}
