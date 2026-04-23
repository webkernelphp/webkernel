<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'webkernel_sqlite';

    public function up(): void
    {
        Schema::connection('webkernel_sqlite')->create('inst_webkernel_settings', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('category');
            $table->string('key');
            $table->string('type')->default('text');
            $table->string('label');
            $table->text('description')->nullable();
            $table->text('value')->nullable();
            $table->text('default_value')->nullable();
            $table->text('options_json')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->string('last_modified_by')->nullable();
            $table->timestamps();
            $table->unique(['category', 'key']);
        });
    }

    public function down(): void
    {
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_webkernel_settings');
    }
};
