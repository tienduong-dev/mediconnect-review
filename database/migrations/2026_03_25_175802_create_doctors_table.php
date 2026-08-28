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
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();

            $table->string('image');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('expertise');
            $table->integer('experience');
            $table->string('education');
            $table->string('profession');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            // $table->string('available_days');
            // $table->string('available_time');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
