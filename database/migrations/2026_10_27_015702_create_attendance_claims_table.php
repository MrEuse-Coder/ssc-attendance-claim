<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_claims', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('year_section', 20);
            $table->string('college', 150);
            $table->string('program', 150);
            $table->string('slot', 20); // morning_login, morning_logout, afternoon_login, afternoon_logout
            $table->timestamps(); // created_at = time the claim was made

            // one claim per college + program + year & section + slot
            // (column lengths are kept short so the index fits MySQL's key-size limit)
            $table->unique(['college', 'program', 'year_section', 'slot'], 'claims_unique_program_section_slot');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_claims');
    }
};
