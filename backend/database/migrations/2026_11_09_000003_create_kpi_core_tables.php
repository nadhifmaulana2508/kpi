<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_units', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('parent_id')->nullable();
            $table->boolean('demo_only')->default(true);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('org_units')->nullOnDelete();
            $table->index(['parent_id', 'type']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('group_name');
            $table->string('access_role');
            $table->boolean('demo_only')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('display_name');
            $table->boolean('demo_only')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('assignments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employee_id');
            $table->string('position_id');
            $table->string('org_unit_id');
            $table->string('supervisor_employee_id')->nullable();
            $table->string('korwil_unit_id')->nullable();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->boolean('demo_only')->default(true);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->restrictOnDelete();
            $table->foreign('supervisor_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('korwil_unit_id')->references('id')->on('org_units')->nullOnDelete();
            $table->index(['employee_id', 'is_primary', 'ends_at']);
            $table->index(['org_unit_id', 'ends_at']);
        });

        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('position_id');
            $table->string('code');
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->string('score_type', 1);
            $table->text('definition');
            $table->string('unit');
            $table->decimal('example_target', 18, 4);
            $table->decimal('weight', 5, 4);
            $table->text('source_note')->nullable();
            $table->boolean('is_quality')->default(false);
            $table->text('application_note')->nullable();
            $table->boolean('demo_only')->default(true);
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->foreign('position_id')->references('id')->on('positions')->restrictOnDelete();
            $table->unique(['position_id', 'code', 'version']);
        });

        Schema::create('cycles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('cadence');
            $table->string('formula_version')->default('demo-prototype-v1');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('OPEN');
            $table->boolean('demo_only')->default(true);
            $table->timestamps();
        });

        Schema::create('performance_contracts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('cycle_id');
            $table->string('subject_type')->default('EMPLOYEE');
            $table->string('employee_id')->nullable();
            $table->string('org_unit_id')->nullable();
            $table->string('position_id')->nullable();
            $table->string('reviewer_employee_id')->nullable();
            $table->json('assignment_snapshot')->nullable();
            $table->string('status')->default('DRAFT');
            $table->boolean('demo_only')->default(true);
            $table->timestamps();

            $table->foreign('cycle_id')->references('id')->on('cycles')->restrictOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->foreign('org_unit_id')->references('id')->on('org_units')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('reviewer_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->index(['cycle_id', 'employee_id', 'status']);
            $table->index(['cycle_id', 'reviewer_employee_id', 'status']);
        });

        Schema::create('contract_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('contract_id');
            $table->string('kpi_definition_id');
            $table->string('code');
            $table->string('name');
            $table->string('score_type', 1);
            $table->string('unit');
            $table->decimal('target', 18, 4);
            $table->decimal('weight', 5, 4);
            $table->decimal('actual', 18, 4)->nullable();
            $table->string('state')->default('PENDING');
            $table->text('reason')->nullable();
            $table->text('note')->nullable();
            $table->json('evidence')->nullable();
            $table->decimal('score', 7, 3)->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('performance_contracts')->cascadeOnDelete();
            $table->foreign('kpi_definition_id')->references('id')->on('kpi_definitions')->restrictOnDelete();
            $table->unique(['contract_id', 'kpi_definition_id']);
        });

        Schema::create('contract_comments', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('contract_id');
            $table->string('actor_employee_id');
            $table->string('item_id')->nullable();
            $table->text('message');
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('performance_contracts')->cascadeOnDelete();
            $table->foreign('actor_employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->foreign('item_id')->references('id')->on('contract_items')->nullOnDelete();
        });

        Schema::create('review_events', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('contract_id');
            $table->string('reviewer_employee_id');
            $table->string('decision');
            $table->text('comment');
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('performance_contracts')->cascadeOnDelete();
            $table->foreign('reviewer_employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->index(['contract_id', 'created_at']);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('actor_employee_id')->nullable();
            $table->string('action');
            $table->string('subject_type');
            $table->string('subject_id');
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->timestamps();

            $table->foreign('actor_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('review_events');
        Schema::dropIfExists('contract_comments');
        Schema::dropIfExists('contract_items');
        Schema::dropIfExists('performance_contracts');
        Schema::dropIfExists('cycles');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('org_units');
    }
};
