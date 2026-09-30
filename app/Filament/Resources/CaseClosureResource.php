<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaseClosureResource\Pages;
use App\Models\FundCase;
use App\Support\CaseClosurePdfBuilder;
use App\Support\CasePhotoProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Форма закрытия кейса — тот же кейс (FundCase), что и в FundCaseResource,
 * но сфокусирована на закрытии: сроки, финансовые/юридические документы,
 * отчёт о проведённых мероприятиях. Своей вкладки в навигации нет
 * ($shouldRegisterNavigation = false) — открывается кнопкой «Закрыть» из
 * таблицы FundCaseResource (см. её table()). Index-страница/роут всё ещё
 * зарегистрированы (без этого Filament не строит breadcrumbs/кнопку «Назад»
 * на edit-странице), но никуда не выводятся и нигде не линкуются напрямую.
 */
class CaseClosureResource extends Resource
{
    protected static ?string $model = FundCase::class;

    protected static ?string $slug = 'case-closures';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'закрытие кейса';

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
                        Forms\Components\Placeholder::make('start_date_display')
                            ->label('Дата начала')
                            ->content(fn (?FundCase $record) => $record?->created_at?->translatedFormat('d.m.Y') ?? '—')
                            ->helperText('Не редактируется — всегда дата создания кейса.'),
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
                            ->afterOrEqual(fn (?FundCase $record) => ($record?->created_at ?? now())->toDateString()),
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
                        Forms\Components\Textarea::make('closure_report_description.en')
                            ->label('Description (English)')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Кейс')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('public_title.ru')
                            ->label('Наименование'),
                        Infolists\Components\TextEntry::make('public_story.ru')
                            ->label('Краткое описание')
                            ->limit(200)
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('budget_minor')
                            ->label('Нужно было собрать')
                            ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
                        Infolists\Components\TextEntry::make('allocated_minor')
                            ->label('Собрано')
                            ->formatStateUsing(fn (int $state) => number_format($state / 100, 0, '.', ' ').' сом'),
                    ]),

                Infolists\Components\Section::make('Сроки кейса')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Дата начала')
                            ->date('d.m.Y'),
                        Infolists\Components\TextEntry::make('end_date')
                            ->label('Дата окончания')
                            ->date('d.m.Y')
                            ->placeholder('Бессрочный'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                'draft' => 'Черновик',
                                'active' => 'Активен',
                                'closed' => 'Закрыт',
                                default => $state,
                            }),
                    ]),

                Infolists\Components\Section::make('Финансовые и юридические документы')
                    ->schema([
                        // ->state() (не ->formatStateUsing() на самом атрибуте) — Filament
                        // для array-cast колонки вызывает formatStateUsing() на каждый
                        // элемент по отдельности, а не на весь массив сразу.
                        Infolists\Components\TextEntry::make('financial_documents_links')
                            ->hiddenLabel()
                            ->html()
                            ->state(function (FundCase $record) {
                                $paths = $record->financial_documents_paths ?? [];

                                if (empty($paths)) {
                                    return '—';
                                }

                                return collect($paths)
                                    ->map(function (string $path) {
                                        $url = Storage::disk('proofs')->temporaryUrl($path, now()->addMinutes(30));

                                        return '<a href="'.e($url).'" target="_blank" class="underline">'.e(basename($path)).'</a>';
                                    })
                                    ->implode('<br>');
                            }),
                    ]),

                Infolists\Components\Section::make('Отчёт по кейсу')
                    ->schema([
                        Infolists\Components\TextEntry::make('closure_report_description.ru')
                            ->hiddenLabel()
                            ->placeholder('Описание не заполнено.'),
                        Infolists\Components\ImageEntry::make('closure_report_photo_paths')
                            ->label('Фото отчёта')
                            ->disk('public')
                            ->height(120),
                    ]),

                Infolists\Components\Actions::make([
                    Infolists\Components\Actions\Action::make('download_pdf')
                        ->label('Скачать PDF')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (FundCase $record) => response()->streamDownload(
                            function () use ($record) {
                                echo CaseClosurePdfBuilder::build($record);
                            },
                            'case-'.$record->id.'-closure-report.pdf'
                        )),
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
            'view' => Pages\ViewCaseClosure::route('/{record}'),
        ];
    }
}
