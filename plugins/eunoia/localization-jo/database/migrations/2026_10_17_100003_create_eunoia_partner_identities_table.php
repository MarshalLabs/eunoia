<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eunoia_partner_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners_partners')->cascadeOnDelete();
            $table->string('type', 16);
            $table->string('value', 32);
            $table->string('country_code', 2)->default('JO');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['partner_id', 'type']);
            $table->index(['type', 'value']);
        });

        Schema::create('eunoia_jo_name_translations', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 64);
            $table->unsignedBigInteger('record_id');
            $table->string('name_en');
            $table->timestamps();

            $table->unique(['table_name', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eunoia_jo_name_translations');
        Schema::dropIfExists('eunoia_partner_identities');
    }
};
