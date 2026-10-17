<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eunoia_jofotara_counters', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->primary();
            $table->unsignedBigInteger('next_icv')->default(1);
        });

        Schema::create('eunoia_jofotara_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('move_id')->unique()->constrained('accounts_account_moves')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('icv');
            $table->string('type_code', 3);
            $table->string('doc_type', 3);
            $table->string('status', 16)->default('queued')->index();
            $table->text('qr_payload')->nullable();
            $table->string('jofotara_uuid')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'icv']);
        });

        Schema::create('eunoia_jofotara_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('eunoia_jofotara_documents')->cascadeOnDelete();
            $table->binary('request_xml')->nullable();
            $table->json('response_json')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('outcome', 16);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eunoia_jofotara_attempts');
        Schema::dropIfExists('eunoia_jofotara_documents');
        Schema::dropIfExists('eunoia_jofotara_counters');
    }
};
