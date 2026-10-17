<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eunoia_jo_tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_id')->unique()->constrained('accounts_taxes')->cascadeOnDelete();
            $table->string('kind', 32)->index();
            $table->string('jofotara_category', 1);
            $table->string('name_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eunoia_jo_tax_profiles');
    }
};
