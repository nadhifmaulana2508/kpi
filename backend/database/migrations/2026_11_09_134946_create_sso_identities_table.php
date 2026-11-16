<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_identities', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('employee_id');
            $table->string('issuer');
            $table->string('subject');
            $table->char('identity_hash', 64)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_identities');
    }
};
