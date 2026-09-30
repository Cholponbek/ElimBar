<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Синглтон-страница (не Resource — строка в site_settings всегда одна,
 * см. UNIQUE(tenant_id) в миграции), редактирует "О нас"/"Контакты" для
 * главной публичной страницы (Cases/Index.vue). Ky/ru через точечную
 * нотацию (about_title.ky) прямо в jsonb-колонку — тот же приём, что
 * FundCaseResource уже использует для public_title/public_story.
 */
class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'О нас и контакты';

    protected static ?string $title = 'О нас и контакты';

    protected static string $view = 'filament.pages.site-settings-page';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(SiteSetting::current()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('О фонде')
                    ->description('Показывается блоком на главной странице сайта.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('about_title.ky')
                            ->label('Заголовок (кыргызча)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('about_title.ru')
                            ->label('Заголовок (русский)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('about_title.en')
                            ->label('Title (English)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('about_body.ky')
                            ->label('Текст (кыргызча)')
                            ->rows(6),
                        Forms\Components\Textarea::make('about_body.ru')
                            ->label('Текст (русский)')
                            ->rows(6),
                        Forms\Components\Textarea::make('about_body.en')
                            ->label('Text (English)')
                            ->rows(6),
                    ]),

                Forms\Components\Section::make('Наши волонтёры')
                    ->description('Подраздел блока "О фонде" на главной странице сайта.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Textarea::make('about_volunteers_body.ky')
                            ->label('Текст (кыргызча)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_volunteers_body.ru')
                            ->label('Текст (русский)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_volunteers_body.en')
                            ->label('Text (English)')
                            ->rows(5),
                    ]),

                Forms\Components\Section::make('Благотворительные ящики нашего фонда')
                    ->description('Подраздел блока "О фонде" на главной странице сайта.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Textarea::make('about_boxes_body.ky')
                            ->label('Текст (кыргызча)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_boxes_body.ru')
                            ->label('Текст (русский)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_boxes_body.en')
                            ->label('Text (English)')
                            ->rows(5),
                    ]),

                Forms\Components\Section::make('Социально-благотворительный магазин')
                    ->description('Подраздел блока "О фонде" на главной странице сайта.')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Textarea::make('about_shop_body.ky')
                            ->label('Текст (кыргызча)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_shop_body.ru')
                            ->label('Текст (русский)')
                            ->rows(5),
                        Forms\Components\Textarea::make('about_shop_body.en')
                            ->label('Text (English)')
                            ->rows(5),
                    ]),

                Forms\Components\Section::make('Контакты фонда')
                    ->description('Отдельный блок на главной странице сайта, после "О фонде".')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('contact_address.ky')
                            ->label('Адрес (кыргызча)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_address.ru')
                            ->label('Адрес (русский)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_address.en')
                            ->label('Address (English)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_reception_phone')
                            ->label('Приёмное отделение (телефон)')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\TextInput::make('contact_partnership_phone')
                            ->label('По вопросам сотрудничества и спонсорства (телефон)')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Электронная почта')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_working_hours.ky')
                            ->label('График работы (кыргызча)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_working_hours.ru')
                            ->label('График работы (русский)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_working_hours.en')
                            ->label('Working hours (English)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_website')
                            ->label('Официальный сайт')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_instagram')
                            ->label('Instagram (ссылка)')
                            ->url()
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        SiteSetting::current()->update($this->form->getState());

        Notification::make()
            ->title('Сохранено')
            ->success()
            ->send();
    }
}
