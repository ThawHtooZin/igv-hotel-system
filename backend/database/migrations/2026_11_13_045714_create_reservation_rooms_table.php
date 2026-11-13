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
        Schema::create('reservation_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();
            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();
            $table->date('assigned_from');
            $table->date('assigned_to');
            $table->decimal('nightly_rate', 10, 2);
            $table->string('status')->default('assigned');
            $table->timestamps();

            $table->index(['room_id', 'assigned_from', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_rooms');
    }
};
