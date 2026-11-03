<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 24)->unique();
            $table->string('access_pin_hash');
            $table->unsignedInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();

            $table->string('status', 30)->default('received');
            $table->string('priority', 20)->default('standard');
            $table->string('violation_type', 60);
            $table->date('incident_date')->nullable();
            $table->string('district', 120);
            $table->string('village', 160)->nullable();
            $table->unsignedTinyInteger('child_age');
            $table->string('child_gender', 20);

            $table->text('child_name')->nullable();
            $table->text('description');
            $table->text('reporter_name')->nullable();
            $table->text('reporter_contact')->nullable();
            $table->string('reporter_contact_hash', 64)->nullable()->index();

            $table->boolean('is_anonymous')->default(true);
            $table->boolean('consent_given')->default(false);

            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closure_outcome', 80)->nullable();
            $table->timestamp('retention_delete_at')->nullable();
            $table->boolean('legal_hold')->default(false);
            $table->string('legacy_reference', 64)->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('priority');
            $table->index('district');
            $table->index('violation_type');
            $table->index('assigned_to');
            $table->index('received_at');
            $table->index('retention_delete_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
