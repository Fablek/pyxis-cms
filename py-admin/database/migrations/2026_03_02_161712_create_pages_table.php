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
        Schema::create('pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->jsonb('content')->nullable()->comment('Bloki i sekcje strony');
            $table->jsonb('seo')->nullable()->comment('Meta tagi i opisy');
            $table->string('status')->default('draft'); // draft, published
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');     
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
