<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_kpi_versions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('position_id');
            $table->unsignedInteger('version');
            $table->string('status')->default('ACTIVE');
            $table->string('created_by_employee_id')->nullable();
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('created_by_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->unique(['position_id', 'version']);
        });

        Schema::create('position_kpi_version_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('position_kpi_version_id');
            $table->string('kpi_definition_id');
            $table->decimal('target', 18, 4);
            $table->decimal('weight', 5, 4);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('position_kpi_version_id')->references('id')->on('position_kpi_versions')->cascadeOnDelete();
            $table->foreign('kpi_definition_id')->references('id')->on('kpi_definitions')->restrictOnDelete();
            $table->unique(['position_kpi_version_id', 'kpi_definition_id'], 'position_kpi_version_items_unique');
        });

        Schema::create('cycle_position_kpi_versions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('cycle_id');
            $table->string('position_id');
            $table->string('position_kpi_version_id');
            $table->timestamps();

            $table->foreign('cycle_id')->references('id')->on('cycles')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('position_kpi_version_id')->references('id')->on('position_kpi_versions')->restrictOnDelete();
            $table->unique(['cycle_id', 'position_id'], 'cycle_position_kpi_versions_unique');
        });

        Schema::table('performance_contracts', function (Blueprint $table) {
            $table->string('position_kpi_version_id')->nullable();
            $table->foreign('position_kpi_version_id', 'contracts_position_kpi_version_fk')
                ->references('id')->on('position_kpi_versions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('performance_contracts', function (Blueprint $table) {
            $table->dropForeign('contracts_position_kpi_version_fk');
            $table->dropColumn('position_kpi_version_id');
        });

        Schema::dropIfExists('cycle_position_kpi_versions');
        Schema::dropIfExists('position_kpi_version_items');
        Schema::dropIfExists('position_kpi_versions');
    }
};
