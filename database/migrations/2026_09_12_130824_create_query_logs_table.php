<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('query_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('connection', 100)->index();
            $table->text('sql');
            $table->json('bindings')->nullable();
            $table->decimal('time_ms', 10, 2);
            $table->string('route_name')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('caller_class')->nullable();
            $table->string('caller_method')->nullable();
            $table->text('explain_result')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_logs');
    }
};
