<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AttendanceClaim extends Model
{
    /** Attendance slots: value => label. */
    public const SLOTS = [
        'morning_login'    => 'Morning Login',
        'morning_logout'   => 'Morning Logout',
        'afternoon_login'  => 'Afternoon Login',
        'afternoon_logout' => 'Afternoon Logout',
    ];

    protected $fillable = ['first_name', 'last_name', 'year_section', 'college', 'program', 'slot'];

    public function getSlotLabelAttribute(): string
    {
        return self::SLOTS[$this->slot] ?? (string) $this->slot;
    }

    /** 'in' for logins, 'out' for logouts (used for badge color). */
    public function getSlotTypeAttribute(): string
    {
        return str_ends_with((string) $this->slot, 'logout') ? 'out' : 'in';
    }

    /**
     * Search by any word across name, year/section, college, program and slot.
     * "juan cruz" matches first_name=Juan, last_name=Dela Cruz;
     * "morning login" matches the morning_login slot.
     * Every word must match at least one column.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $words = preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $like = '%' . addcslashes($word, '%_\\') . '%';

            $query->where(function (Builder $q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('year_section', 'like', $like)
                    ->orWhere('college', 'like', $like)
                    ->orWhere('program', 'like', $like)
                    ->orWhere('slot', 'like', $like);
            });
        }

        return $query;
    }
}
