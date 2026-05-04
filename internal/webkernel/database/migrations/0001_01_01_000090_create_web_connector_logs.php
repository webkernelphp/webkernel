<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Webkernel connector operation logs.
 *
 * Audit trail of all system updates, module installations, and removals.
 */
return new class extends Migration
{
    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection('webkernel_sqlite')->create('connector_logs', function (Blueprint $table) {
            $table->char('id', self::ID_LENGTH)->primary();
            $table->enum('type', ['kernel_update', 'module_install', 'module_remove']);
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->string('source'); // registry/provider name
            $table->string('vendor')->nullable();
            $table->string('slug')->nullable();
            $table->string('version')->nullable();
            $table->longText('result')->nullable(); // encrypted result/error
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('webkernel_sqlite')->dropIfExists('connector_logs');
    }
};
