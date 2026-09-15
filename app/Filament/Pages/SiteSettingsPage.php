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
                Forms\Components\Section::make('О нас')
                    ->description('Показывается блоком на главной странице сайта.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('about_title.ky')
                            ->label('Заголовок (кыргызча)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('about_title.ru')
                            ->label('Заголовок (русский)')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('about_body.ky')
                            ->label('Текст (кыргызча)')
                            ->rows(6),
                        Forms\Components\Textarea::make('about_body.ru')
                            ->label('Текст (русский)')
                            ->rows(6),
                    ]),

                Forms\Components\Section::make('Контакты')
                    ->description('Показывается блоком на главной странице сайта.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('contact_address.ky')
                            ->label('Адрес (кыргызча)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_address.ru')
                            ->label('Адрес (русский)')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(30),
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_instagram')
                            ->label('Instagram (ссылка)')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_facebook')
                            ->label('Facebook (ссылка)')
                            ->url()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('contact_whatsapp')
                            ->label('WhatsApp (ссылка)')
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
