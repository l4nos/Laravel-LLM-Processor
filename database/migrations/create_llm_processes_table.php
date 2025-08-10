<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('llm_processes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('model_class');
            $table->json('dependencies')->nullable();
            $table->text('system_prompt');
            $table->text('user_prompt');
            $table->string('model');
            $table->decimal('temperature', 3, 2)->default(0.7);
            $table->integer('max_output_tokens')->default(4096);
            $table->enum('output_type', ['text', 'json'])->default('text');
            $table->json('structured_output_schema')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('terminate_on_missing_data')->default(false);
            $table->boolean('use_web_search')->default(false);
            $table->boolean('use_reasoning')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('model_class');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_processes');
    }
};