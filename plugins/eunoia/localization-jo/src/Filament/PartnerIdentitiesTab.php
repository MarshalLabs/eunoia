<?php

namespace Eunoia\LocalizationJo\Filament;

use Eunoia\LocalizationJo\Enums\IdentityType;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

/**
 * "Tax & identity" tab on the partner form (JO-06): TIN, national ID, commercial registration, passport.
 */
class PartnerIdentitiesTab
{
    public static function make(): Tab
    {
        return Tab::make(__('eunoia-localization-jo::identities.tab'))
            ->icon('heroicon-o-identification')
            ->schema([
                Repeater::make('identities')
                    ->hiddenLabel()
                    ->relationship('identities')
                    ->addActionLabel(__('eunoia-localization-jo::identities.add'))
                    ->columns(2)
                    ->defaultItems(0)
                    ->schema([
                        Select::make('type')
                            ->label(__('eunoia-localization-jo::identities.fields.type'))
                            ->options(IdentityType::class)
                            ->required()
                            ->live()
                            ->distinct(),
                        TextInput::make('value')
                            ->label(__('eunoia-localization-jo::identities.fields.value'))
                            ->required()
                            ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric'])
                            ->dehydrateStateUsing(fn (?string $state): string => IdentityType::clean((string) $state))
                            ->rule(fn (Get $get) => function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                $type = IdentityType::tryFrom((string) $get('type'));

                                if ($type && ! $type->isValid((string) $value)) {
                                    $fail(__('eunoia-localization-jo::identities.invalid', ['type' => $type->getLabel()]));
                                }
                            }),
                    ]),
            ]);
    }
}
