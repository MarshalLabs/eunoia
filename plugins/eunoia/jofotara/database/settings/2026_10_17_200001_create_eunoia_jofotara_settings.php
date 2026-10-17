<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->addEncrypted('eunoia_jofotara.client_id', null);
        $this->migrator->addEncrypted('eunoia_jofotara.secret_key', null);
        $this->migrator->add('eunoia_jofotara.tin', null);
        $this->migrator->add('eunoia_jofotara.income_source_sequence', null);
        $this->migrator->add('eunoia_jofotara.seller_name', null);
        $this->migrator->add('eunoia_jofotara.seller_registration', 'general_sales');
        $this->migrator->add('eunoia_jofotara.mode', 'mock');
        $this->migrator->add('eunoia_jofotara.auto_submit', true);
    }

    public function down(): void
    {
        foreach (['client_id', 'secret_key', 'tin', 'income_source_sequence', 'seller_name', 'seller_registration', 'mode', 'auto_submit'] as $key) {
            $this->migrator->deleteIfExists("eunoia_jofotara.{$key}");
        }
    }
};
