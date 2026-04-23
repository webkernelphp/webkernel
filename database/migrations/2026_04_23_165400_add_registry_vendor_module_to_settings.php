<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    public function up(): void
    {
        Schema::connection($this->connection)->table('inst_webkernel_settings', function (Blueprint $table) {
            $table->string('registry')->default('webkernel')->after('category');
            $table->string('vendor')->nullable()->after('registry');
            $table->string('module')->nullable()->after('vendor');
            $table->boolean('is_custom')->default(false)->after('is_sensitive');
            $table->dateTime('last_touched_at')->nullable()->after('last_modified_by');

            $table->index(['registry', 'vendor', 'module']);
            $table->index('is_custom');
            $table->index('last_touched_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('inst_webkernel_settings', function (Blueprint $table) {
            $table->dropIndex(['registry', 'vendor', 'module']);
            $table->dropIndex(['is_custom']);
            $table->dropIndex(['last_touched_at']);
            $table->dropColumn(['registry', 'vendor', 'module', 'is_custom', 'last_touched_at']);
        });
    }
};
