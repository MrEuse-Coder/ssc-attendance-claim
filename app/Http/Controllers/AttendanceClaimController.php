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
     * Colleges and their programs. These are examples: edit them to match your campus.
     * Programs that people type in manually are remembered and offered in the dropdown next time.
     */
    public const COLLEGE_PROGRAMS = [
        'CCS' => [
          'BSIT'
        ],
        'COED' => [
           'BTVTED',
            'BTLED',
            'BSED A ENGLISH',
            'BSED B FILIPINO',
            'BSED C SOCIAL STUDIES',
            'BEED'
        ],
        'CBMA' => [
'AIAS'
        ],
        'COE' => [
'ENGINEERING'
        ],
        'CAS' => [
'BACOMM'
        ],
        'CCJE' => [
        'CRIMINOLOGY'
        ],
        'CON' => [
        'NURSING'
        ],
        'CHM' => [
            'BSTM',
            'BSHM'

        ],
        'COT' => [
        'AUTOMOTIVE',
            'ELECTRICAL',
            'DRAFTING',
            'CULINARY',
            'INDUSTRIAL TECHNOLOGY',
            'FASHION AND DESIGN'
        ],
    ];

    /** Year levels: value => label. */
    public const YEARS = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year',
    ];

    /** Sections. Edit to match your campus. */
    public const SECTIONS = ['A', 'B', 'C', 'D', 'E', 'F','G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];

    public static function colleges(): array
    {
        return array_keys(self::COLLEGE_PROGRAMS);
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

        foreach (self::COLLEGE_PROGRAMS as $college => $listed) {
            $known = array_map('mb_strtolower', $listed);

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
        $search = trim((string) $request->query('q', ''));

        $slot = $request->query('slot');
        $slot = (is_string($slot) && isset(AttendanceClaim::SLOTS[$slot])) ? $slot : null;

        // Filters ('' = not filtering)
        $college = $request->query('college');
        $college = (is_string($college) && isset(self::COLLEGE_PROGRAMS[$college])) ? $college : '';

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
            'colleges'   => self::colleges(),
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
        $input['year_section'] = (
            is_string($input['year'] ?? null) && $input['year'] !== '' &&
            is_string($input['section'] ?? null) && $input['section'] !== ''
        ) ? $input['year'] . '-' . $input['section'] : null;
        $request->merge($input);

        $slotKey   = $input['slot'] ?? null;
        $slotLabel = is_string($slotKey) ? (AttendanceClaim::SLOTS[$slotKey] ?? 'this slot') : 'this slot';

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'year'       => ['required', Rule::in(array_keys(self::YEARS))],
            'section'    => ['required', Rule::in(self::SECTIONS)],
            'college'    => ['required', Rule::in(self::colleges())],
            // free text: picked from the dropdown or typed in
            'program'    => ['required', 'string', 'min:2', 'max:150'],
            'slot'       => ['required', Rule::in(array_keys(AttendanceClaim::SLOTS))],
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

        $valid = Validator::make($in, [
            'college' => ['required', Rule::in(self::colleges())],
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
}
