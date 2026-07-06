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
        Schema::create('post_versions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generated_post_id')
                ->constrained('generated_posts')
                ->cascadeOnDelete();

            $table->unsignedInteger('version_number');

            $table->string('hook_propose', 280)->nullable();
            $table->json('body_points')->nullable();
            $table->json('suggested_hashtags')->nullable();
            $table->text('tone_compliance_justification')->nullable();
            $table->json('raw_payload')->nullable();

            $table->string('source')->default('assistant');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_versions');
    }
};
