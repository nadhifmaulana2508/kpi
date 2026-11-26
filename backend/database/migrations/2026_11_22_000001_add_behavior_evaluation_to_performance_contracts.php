<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_contracts', function (Blueprint $table): void {
            $table->json('behavior_evaluation')->nullable()->after('assignment_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('performance_contracts', function (Blueprint $table): void {
            $table->dropColumn('behavior_evaluation');
        });
    }
};
