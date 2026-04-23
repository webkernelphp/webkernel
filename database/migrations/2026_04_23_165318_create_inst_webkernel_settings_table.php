<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    public function up(): void
    {
        Schema::connection($this->connection)->create('inst_webkernel_setting_categories', function (Blueprint $table) {
            $table->string('key')->primary(); // unique identifier (used for translation key)

            $table->string('label'); // fallback label
            $table->text('description')->nullable();

            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);

            $table->boolean('is_system')->default(false); // prevent deletion if needed

            $table->json('meta_json')->nullable(); // future-proof (UI grouping, permissions, etc)

            $table->timestamps();
        });

        Schema::connection($this->connection)->create('inst_webkernel_settings', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            $table->string('category');
            $table->string('registry')->default('webkernel');
            $table->string('vendor')->nullable();
            $table->string('module')->nullable();
            $table->string('key');

            $table->string('type')->default('text');

            $table->string('label');
            $table->text('description')->nullable();

            $table->longText('value')->nullable();
            $table->longText('default_value')->nullable();

            $table->json('options_json')->nullable();

            $table->boolean('is_sensitive')->default(false);
            $table->boolean('is_custom')->default(false);

            $table->json('meta_json')->nullable();
            $table->string('enum_class')->nullable();

            $table->string('introduced_in_version');
            $table->string('last_modified_by')->nullable();
            $table->dateTime('last_touched_at')->nullable();

            $table->string('depends_on_key')->nullable();
            $table->string('depends_on_value')->nullable();
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['category', 'key']);
            $table->index(['registry', 'vendor', 'module']);
            $table->index('is_custom');
            $table->index('last_touched_at');
            $table->index(['depends_on_key', 'depends_on_value']);
            $table->index('sort_order');

            $table->foreign('category')
                ->references('key')
                ->on('inst_webkernel_setting_categories')
                ->cascadeOnDelete();
        });

        Schema::connection($this->connection)->create('inst_webkernel_settings_history', function (Blueprint $table) {
            $table->char('id', 26)->primary();

            $table->char('setting_id', 26);

            $table->string('category');
            $table->string('key');

            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();

            $table->string('changed_by')->nullable();

            $table->timestamps();

            $table->foreign('setting_id')
                ->references('id')
                ->on('inst_webkernel_settings')
                ->cascadeOnDelete();

            $table->index(['category', 'key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('inst_webkernel_settings_history');
        Schema::connection($this->connection)->dropIfExists('inst_webkernel_settings');
        Schema::connection($this->connection)->dropIfExists('inst_webkernel_setting_categories');
    }
};
