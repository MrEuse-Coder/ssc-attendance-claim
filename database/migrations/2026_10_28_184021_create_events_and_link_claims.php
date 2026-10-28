<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'attendance_claims';
    private const OLD_INDEX = 'claims_unique_program_section_slot';
    private const NEW_INDEX = 'claims_event_unique';

    public function up(): void
    {
        Schema::create('events', function (Blueprint $t) {
            $t->id();
            $t->string('name', 150)->unique();
            $t->timestamps();
        });

        Schema::table(self::TABLE, function (Blueprint $t) {
            $t->foreignId('event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Only if claims already exist: keep them by moving them into a first event.
        // On an empty database no event is created, so the app asks you to add one.
        if (DB::table(self::TABLE)->exists()) {
            $eventId = DB::table('events')->insertGetId([
                'name'       => 'Intramurals – Day 1',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table(self::TABLE)->update(['event_id' => $eventId]);
        }

        Schema::table(self::TABLE, function (Blueprint $t) {
            $t->dropUnique(self::OLD_INDEX);
            // one claim per event + college + program + year & section + slot
            $t->unique(['event_id', 'college', 'program', 'year_section', 'slot'], self::NEW_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table(self::TABLE, function (Blueprint $t) {
            $t->dropUnique(self::NEW_INDEX);
            $t->dropConstrainedForeignId('event_id');
            $t->unique(['college', 'program', 'year_section', 'slot'], self::OLD_INDEX);
        });

        Schema::dropIfExists('events');
    }
};
