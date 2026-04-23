<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('webkernel_sqlite')->create('inst_webkernel_background_tasks', function (Blueprint $t) {
            $t->char('id', 26)->primary();
            $t->string('user_id')->nullable();
            $t->string('type');
            $t->string('label');
            $t->json('payload')->nullable();
            $t->string('status')->default('pending');
            $t->text('output')->nullable();
            $t->text('error')->nullable();
            $t->text('suggested_action')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index('status');
            $t->index('user_id');
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('webkernel_sqlite')->dropIfExists('inst_webkernel_background_tasks');
    }
};
