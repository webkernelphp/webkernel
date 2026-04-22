<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ID_LENGTH = 26;

    public function up(): void
    {
        Schema::connection('webkernel_sqlite')->create('modules_src_keys', function (Blueprint $table) {
            $table->char('id', self::ID_LENGTH)->primary();
            $table->string('registry'); // e.g. "github-com", "gitlab-com"
            $table->string('vendor')->nullable(); // GitHub username or org
            $table->longText('token_encrypted'); // CryptData::encrypt()
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['registry', 'vendor']);
            $table->index('registry');
        });
    }

    public function down(): void
    {
        Schema::connection('webkernel_sqlite')->dropIfExists('modules_src_keys');
    }
};
