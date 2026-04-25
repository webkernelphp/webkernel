<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-business (and optionally per-module) database connection credentials.
 *
 * Passwords are encrypted with APP_KEY via Laravel's encrypt() helper.
 * Decrypted values exist only in memory for the duration of the request.
 *
 * Resolution cascade (see DatabaseConnectionResolver):
 *   1. module-specific (business_id + module_id)
 *   2. business default (business_id, module_id IS NULL)
 *   3. instance default (Laravel config database.default)
 */
return new class extends Migration
{
    protected $connection = 'webkernel_sqlite';

    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection($this->connection)->create('db_connections', function (Blueprint $table): void {
            $table->char('id', self::ID_LENGTH)->primary();

            $table->char('business_id', self::ID_LENGTH);
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->cascadeOnDelete();

            // NULL = business-level default; set = module-specific override
            $table->char('module_id', self::ID_LENGTH)->nullable();
            $table->foreign('module_id')
                  ->references('id')
                  ->on('modules')
                  ->nullOnDelete();

            // 'mysql' | 'pgsql' | 'sqlite'
            $table->string('driver', 10);

            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('database');
            $table->string('username')->nullable();

            // AES-256-GCM via APP_KEY — never stored in plaintext
            $table->text('password_encrypted');

            // Timestamp of last successful connection test
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // Cascade resolution lookup
            $table->index(['business_id', 'module_id'], 'db_conn_lookup');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('db_connections');
    }
};
