<?php

namespace Eunoia\JoFotara\Filament\Pages;

use Eunoia\JoFotara\Enums\SellerRegistration;
use Eunoia\JoFotara\Settings\JoFotaraSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkul\Support\Filament\Clusters\Settings;

/**
 * JoFotara settings (JF-01). Client-Id / Secret-Key are write-only: never sent back to the browser;
 * leaving the field empty keeps the stored value.
 */
class ManageJoFotara extends SettingsPage
{
    protected static ?string $slug = 'eunoia/jofotara';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?int $navigationSort = 50;

    protected static string $settings = JoFotaraSettings::class;

    protected static ?string $cluster = Settings::class;

    public static function getNavigationGroup(): ?string
    {
        return __('eunoia-jofotara::settings.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('eunoia-jofotara::settings.title');
    }

    public function getTitle(): string
    {
        return __('eunoia-jofotara::settings.title');
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('page_eunoia_jofotara_manage') || (bool) auth()->user()?->hasRole(['Admin', 'super_admin']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['has_client_id'] = filled($data['client_id'] ?? null);
        $data['has_secret_key'] = filled($data['secret_key'] ?? null);
        $data['client_id'] = null;
        $data['secret_key'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $current = app(JoFotaraSettings::class);

        $data['client_id'] = filled($data['client_id'] ?? null) ? trim($data['client_id']) : $current->client_id;
        $data['secret_key'] = filled($data['secret_key'] ?? null) ? trim($data['secret_key']) : $current->secret_key;
        unset($data['has_client_id'], $data['has_secret_key']);

        if ($data['seller_registration'] instanceof SellerRegistration) {
            $data['seller_registration'] = $data['seller_registration']->value;
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('eunoia-jofotara::settings.sections.seller'))
                ->columns(2)
                ->schema([
                    TextInput::make('tin')
                        ->label(__('eunoia-jofotara::settings.fields.tin'))
                        ->regex('/^\d{8,12}$/')
                        ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('income_source_sequence')
                        ->label(__('eunoia-jofotara::settings.fields.income_source_sequence'))
                        ->helperText(__('eunoia-jofotara::settings.help.income_source_sequence'))
                        ->regex('/^\d{1,20}$/')
                        ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('seller_name')
                        ->label(__('eunoia-jofotara::settings.fields.seller_name'))
                        ->helperText(__('eunoia-jofotara::settings.help.seller_name')),
                    Select::make('seller_registration')
                        ->label(__('eunoia-jofotara::settings.fields.seller_registration'))
                        ->options(SellerRegistration::class)
                        ->required(),
                ]),
            Section::make(__('eunoia-jofotara::settings.sections.connection'))
                ->columns(2)
                ->schema([
                    TextInput::make('client_id')
                        ->label(__('eunoia-jofotara::settings.fields.client_id'))
                        ->password()
                        ->revealable(false)
                        ->autocomplete('off')
                        ->placeholder(fn ($get): string => $get('has_client_id') ? __('eunoia-jofotara::settings.help.stored') : '')
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('secret_key')
                        ->label(__('eunoia-jofotara::settings.fields.secret_key'))
                        ->password()
                        ->revealable(false)
                        ->autocomplete('new-password')
                        ->placeholder(fn ($get): string => $get('has_secret_key') ? __('eunoia-jofotara::settings.help.stored') : '')
                        ->extraInputAttributes(['dir' => 'ltr']),
                    Select::make('mode')
                        ->label(__('eunoia-jofotara::settings.fields.mode'))
                        ->options([
                            'mock' => __('eunoia-jofotara::settings.modes.mock'),
                            'live' => __('eunoia-jofotara::settings.modes.live'),
                        ])
                        ->disableOptionWhen(fn (string $value): bool => $value === 'live' && ! (config('eunoia-jofotara.allow_live') && app()->isProduction()))
                        ->helperText(__('eunoia-jofotara::settings.help.mode'))
                        ->required(),
                    Toggle::make('auto_submit')
                        ->label(__('eunoia-jofotara::settings.fields.auto_submit'))
                        ->helperText(__('eunoia-jofotara::settings.help.auto_submit')),
                ]),
        ]);
    }
}
