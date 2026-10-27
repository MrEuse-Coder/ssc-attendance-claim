<?php

// ONLY for a database that already ran an earlier version of the create migration.
// Requires Laravel 11+ (uses Schema::getIndexes). Otherwise use `php artisan migrate:fresh` in development.
// Move this file up into database/migrations/ and run `php artisan migrate`.
// Existing rows get slot = morning_login and program = Unspecified.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'attendance_claims';
    private const NEW_INDEX = 'claims_unique_program_section_slot';

    public function up(): void
    {
        $hasSlot    = Schema::hasColumn(self::TABLE, 'slot');
        $hasProgram = Schema::hasColumn(self::TABLE, 'program');

        Schema::table(self::TABLE, function (Blueprint $table) use ($hasSlot, $hasProgram) {
            if (! $hasSlot) {
                $table->string('slot', 20)->default('morning_login')->after('college');
            }
            if (! $hasProgram) {
                $table->string('program', 150)->default('Unspecified')->after('college');
            }
        });

        $indexes = collect(Schema::getIndexes(self::TABLE))->pluck('name')->all();

        if (in_array(self::NEW_INDEX, $indexes, true)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($indexes) {
            foreach (['claims_unique_college_section', 'claims_unique_section_slot'] as $old) {
                if (in_array($old, $indexes, true)) {
                    $table->dropUnique($old);
                }
            }
            $table->unique(['college', 'program', 'year_section', 'slot'], self::NEW_INDEX);
        });
    }

    public function down(): void
    {
        // Intentionally left empty: downgrading would drop data columns.
    }
};
