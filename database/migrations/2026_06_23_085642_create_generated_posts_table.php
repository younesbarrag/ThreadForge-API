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
        Schema::create('generated_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('campaign_blueprint_id')
                ->constrained('campaign_blueprints')
                ->cascadeOnDelete();

            $table->foreignId('raw_content_id')
                ->unique()
                ->constrained('raw_contents')
                ->cascadeOnDelete();

            $table->string('hook_propose', 280);
            $table->json('body_points');
            $table->unsignedInteger('technical_readability_score');
            $table->json('suggested_hashtags');
            $table->text('tone_compliance_justification');
            $table->json('raw_payload')->nullable();
            $table->string('publication_status')->default('draft');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_posts');
    }
};