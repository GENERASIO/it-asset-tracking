<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('issue');
            $table->text('action_taken')->nullable();
            $table->string('technician')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->date('reported_at');
            $table->date('resolved_at')->nullable();
            $table->enum('status', ['open', 'in_progress', 'done'])->default('open');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};