<?php

namespace App\Http\Controllers;

use App\Models\AttendanceClaim;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceClaimController extends Controller
{
    /**
     * Colleges and their programs. Colleges and programs that people type manually
     * are remembered and offered in the dropdowns next time.
     */
    public const COLLEGE_PROGRAMS = [
        'CCS [COLLEGE OF COMPUTER STUDIES]'  => ['INFORMATION TECHNOLOGY'],
        'COED [COLLEGE OF EDUCATION]' => [
            'BSED A ENGLISH',
            'BSED B FILIPINO',
            'BSED C SOCIAL STUDIES',
            'BTVTED',
            'BTLED',
            'BEED',
        ],
        'CBMA [COLLEGE OF BUSINESS MANAGEMENT AND ACCOUNTANCY]' => ['AIAS','BSBA','ENTREPRENEURSHIP'],
        'COE [COLLEGE OF ENGINEERING]'  => ['ENGINEERING'],
        'CAS [COLLEGE OF ARTS AND SCIENCES]'  => ['BACOMM'],
        'CCJE [COLLEGE OF CRIMINAL JUSTICE EDUCATION]' => ['CRIMINOLOGY'],
        'CON [COLLEGE OF NURSING]'  => ['NURSING'],
        'CHM [COLLEGE OF HOSPITALITY MANAGEMENT]'  => ['BSTM', 'BSHM'],
        'COT [COLLEGE OF TECHNOLOGY]'  => [
            'AUTOMOTIVE',
            'ELECTRICAL',
            'DRAFTING',
            'CULINARY',
            'INDUSTRIAL TECHNOLOGY',
            'FASHION AND APPAREL',
            'ELECTRONICS',
            'CONSTRUCTION',
        ],
        'FISHERIES' => ['FISHERIES']
    ];

    /** Year levels: value => label. */
    public const YEARS = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year',
    ];

    /** Sections. Edit to match your campus. */
    public const SECTIONS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];

    /** Per-request cache for collegeOptions(). */
    private ?array $collegeCache = null;

    public static function colleges(): array
    {
        return array_keys(self::COLLEGE_PROGRAMS);
    }

    /** Listed colleges plus any custom colleges people typed before. */
    private function collegeOptions(): array
    {
        if ($this->collegeCache !== null) {
            return $this->collegeCache;
        }

        $listed = self::colleges();
        $known  = array_map('mb_strtolower', $listed);

        $extra = AttendanceClaim::query()
            ->distinct()
            ->pluck('college')
            ->reject(fn ($c) => in_array(mb_strtolower($c), $known, true))
            ->unique(fn ($c) => mb_strtolower($c))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return $this->collegeCache = array_merge($listed, $extra);
    }

    /** Match a known college ignoring case; otherwise store typed colleges in UPPERCASE. */
    private function normalizeCollege(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        foreach ($this->collegeOptions() as $known) {
            if (mb_strtolower($known) === mb_strtolower($value)) {
                return $known;
            }
        }

        return mb_strtoupper($value);
    }

    /** Listed programs per college, plus any custom programs people typed before. */
    private function programOptions(): array
    {
        $custom = AttendanceClaim::query()
            ->select('college', 'program')
            ->distinct()
            ->where('program', '!=', 'Unspecified')
            ->get()
            ->groupBy('college');

        $options = [];

        foreach ($this->collegeOptions() as $college) {
            $listed = self::COLLEGE_PROGRAMS[$college] ?? [];
            $known  = array_map('mb_strtolower', $listed);

            $extra = collect($custom->get($college, []))
                ->pluck('program')
                ->unique(fn ($p) => mb_strtolower($p))
                ->reject(fn ($p) => in_array(mb_strtolower($p), $known, true))
                ->sort(SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            $options[$college] = array_merge($listed, $extra);
        }

        return $options;
    }

    public function index(Request $request): View
    {
        $search   = trim((string) $request->query('q', ''));
        $colleges = $this->collegeOptions();

        $slot = $request->query('slot');
        $slot = (is_string($slot) && isset(AttendanceClaim::SLOTS[$slot])) ? $slot : null;

        // Filters ('' = not filtering)
        $college = $request->query('college');
        $college = (is_string($college) && in_array($college, $colleges, true)) ? $college : '';

        // A program filter only makes sense inside a college.
        $program = $request->query('program');
        $program = ($college !== '' && is_string($program)) ? trim($program) : '';

        $year = $request->query('year');
        $year = (is_string($year) && isset(self::YEARS[$year])) ? $year : '';

        $section = $request->query('section');
        $section = (is_string($section) && in_array($section, self::SECTIONS, true)) ? $section : '';

        $claims = AttendanceClaim::query()
            ->search($search)
            ->when($slot, fn ($q) => $q->where('slot', $slot))
            ->when($college !== '', fn ($q) => $q->where('college', $college))
            ->when($program !== '', fn ($q) => $q->where('program', $program))
            // year_section is stored as "4-A"
            ->when($year !== '' && $section !== '', fn ($q) => $q->where('year_section', "{$year}-{$section}"))
            ->when($year !== '' && $section === '', fn ($q) => $q->where('year_section', 'like', "{$year}-%"))
            ->when($year === '' && $section !== '', fn ($q) => $q->where('year_section', 'like', "%-{$section}"))
            ->latest()
            ->orderBy('year_section')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id') // tie-breaker for stable pagination
            ->simplePaginate(10)
            ->withQueryString();

        return view('index', [
            'claims'     => $claims,
            'search'     => $search,
            'slot'       => $slot,
            'filters'    => [
                'college' => $college,
                'program' => $program,
                'year'    => $year,
                'section' => $section,
            ],
            'slots'      => AttendanceClaim::SLOTS,
            'slotCounts' => AttendanceClaim::query()
                ->selectRaw('slot, COUNT(*) as c')
                ->groupBy('slot')
                ->pluck('c', 'slot'),
            'colleges'   => $colleges,
            'programs'   => $this->programOptions(),
            'years'      => self::YEARS,
            'sections'   => self::SECTIONS,
            'total'      => AttendanceClaim::count(),
            'today'      => AttendanceClaim::whereDate('created_at', today())->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Normalize spacing, then combine the two dropdowns into "4-A".
        $input = array_map(
            fn ($v) => is_string($v) ? preg_replace('/\s+/', ' ', trim($v)) : $v,
            $request->only(['first_name', 'last_name', 'year', 'section', 'college', 'program', 'slot'])
        );
        $input['college'] = $this->normalizeCollege($input['college'] ?? null);
        $input['year_section'] = (
            is_string($input['year'] ?? null) && $input['year'] !== '' &&
            is_string($input['section'] ?? null) && $input['section'] !== ''
        ) ? $input['year'] . '-' . $input['section'] : null;
        $request->merge($input);

        $slotKey   = $input['slot'] ?? null;
        $slotLabel = is_string($slotKey) ? (AttendanceClaim::SLOTS[$slotKey] ?? 'this slot') : 'this slot';

        $data = $request->validate([
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'year'         => ['required', Rule::in(array_keys(self::YEARS))],
            'section'      => ['required', Rule::in(self::SECTIONS)],
            // free text: picked from the dropdown or typed in
            'college'      => ['required', 'string', 'min:2', 'max:50'],
            'program'      => ['required', 'string', 'min:2', 'max:150'],
            'slot'         => ['required', Rule::in(array_keys(AttendanceClaim::SLOTS))],
            'year_section' => ['nullable', 'string', 'max:20'],
        ]);

        // college + program + year & section can claim each slot only once.
        // If it was already claimed, tell the user who claimed it.
        $existing = $this->findExisting($data['college'], $data['program'], $data['year_section'], $data['slot']);
        if ($existing) {
            return $this->alreadyClaimed($existing);
        }

        try {
            AttendanceClaim::create(
                Arr::only($data, ['first_name', 'last_name', 'year_section', 'college', 'program', 'slot'])
            );
        } catch (QueryException $e) {
            // Two people submitting at the same moment: the unique index caught the second one.
            $existing = $this->findExisting($data['college'], $data['program'], $data['year_section'], $data['slot']);
            if ($existing) {
                return $this->alreadyClaimed($existing);
            }
            throw $e;
        }

        return redirect()
            ->route('claims.index')
            ->with('success', "{$data['first_name']} {$data['last_name']}: {$slotLabel} claimed.");
    }

    /** Live check used by the form: has this slot already been claimed, and by whom? */
    public function check(Request $request): JsonResponse
    {
        $clean = fn ($v) => is_string($v) ? preg_replace('/\s+/', ' ', trim($v)) : null;

        $in = [
            'college' => $clean($request->query('college')),
            'program' => $clean($request->query('program')),
            'year'    => $clean($request->query('year')),
            'section' => $clean($request->query('section')),
            'slot'    => $clean($request->query('slot')),
        ];
        $in['college'] = $this->normalizeCollege($in['college']);

        $valid = Validator::make($in, [
            'college' => ['required', 'string', 'min:2', 'max:50'],
            'program' => ['required', 'string', 'min:2', 'max:150'],
            'year'    => ['required', Rule::in(array_keys(self::YEARS))],
            'section' => ['required', Rule::in(self::SECTIONS)],
            'slot'    => ['required', Rule::in(array_keys(AttendanceClaim::SLOTS))],
        ]);

        if ($valid->fails()) {
            return response()->json(['claimed' => false]);
        }

        $claim = $this->findExisting($in['college'], $in['program'], $in['year'] . '-' . $in['section'], $in['slot']);

        return response()->json($claim ? $this->claimedInfo($claim) : ['claimed' => false]);
    }

    private function findExisting(string $college, string $program, string $yearSection, string $slot): ?AttendanceClaim
    {
        return AttendanceClaim::where('college', $college)
            ->where('program', $program)
            ->where('year_section', $yearSection)
            ->where('slot', $slot)
            ->first();
    }

    private function claimedInfo(AttendanceClaim $claim): array
    {
        return [
            'claimed' => true,
            'name'    => "{$claim->first_name} {$claim->last_name}",
            'slot'    => $claim->slot_label,
            'group'   => "{$claim->year_section} · {$claim->program}",
            'when'    => $claim->created_at->format('M d, Y · h:i A'),
        ];
    }

    private function alreadyClaimed(AttendanceClaim $claim): RedirectResponse
    {
        $i = $this->claimedInfo($claim);

        return back()->withInput()->withErrors([
            'year_section' => "{$i['slot']} for {$i['group']} was already claimed by {$i['name']} on {$i['when']}.",
        ]);
    }

    public function destroy(AttendanceClaim $claim): RedirectResponse
    {
        $claim->delete();

        return back()->with('success', 'Claim removed.');
    }

    public function destroyAll(): RedirectResponse
    {
        $count = AttendanceClaim::query()->delete();

        return redirect()
            ->route('claims.index')
            ->with('success', "All claims removed ({$count}).");
    }
}
