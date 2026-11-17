<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_role_grants', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('employee_id');
            $table->string('access_role');
            $table->boolean('active')->default(true);
            $table->boolean('demo_only')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->string('granted_by_employee_id')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('granted_by_employee_id')->references('id')->on('employees')->nullOnDelete();
            $table->unique(['employee_id', 'access_role']);
            $table->index(['employee_id', 'active', 'demo_only']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_role_grants');
    }
};
