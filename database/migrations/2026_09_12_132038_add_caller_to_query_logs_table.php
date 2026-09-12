<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('query_logs', function (Blueprint $table): void {
            $table->string('caller_class')->nullable();
            $table->string('caller_method')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('query_logs', function (Blueprint $table): void {
            $table->dropColumn(['caller_class', 'caller_method']);
        });
    }
};
