<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')
                ->constrained('guests')
                ->restrictOnDelete();
            $table->string('confirmation_code')->unique();
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->unsignedInteger('adult_count');
            $table->unsignedInteger('child_count')->default(0);
            $table->string('status')->default('tentative');
            $table->string('source')->default('staff');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['arrival_date', 'departure_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
