<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layup_page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('layup_pages')->cascadeOnDelete();
            $table->json('content');
            $table->string('note')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layup_page_revisions');
    }
};
