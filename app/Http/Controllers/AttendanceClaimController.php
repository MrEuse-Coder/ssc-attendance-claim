<?php

namespace App\Http\Controllers;

use App\Models\AttendanceClaim;
use App\Models\Event;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /** The event picked via ?event=ID, falling back to the newest event. */
    private function currentEvent(Request $request): ?Event
    {
        $id = $request->query('event');

        return (is_string($id) && ctype_digit($id) ? Event::find($id) : null)
            ?? Event::orderByDesc('id')->first();
    }

    public function index(Request $request): View
    {
        $search   = trim((string) $request->query('q', ''));
        $colleges = $this->collegeOptions();

        $events = Event::orderBy('id')->get();
        $event  = $this->currentEvent($request);

        // No events yet: ask the user to add the first one.
        if (! $event) {
            return view('no-events');
        }

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
            ->where('event_id', $event->id)
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
            'events'     => $events,
            'event'      => $event,
            'slots'      => AttendanceClaim::SLOTS,
            'slotCounts' => AttendanceClaim::query()
                ->where('event_id', $event->id)
                ->selectRaw('slot, COUNT(*) as c')
                ->groupBy('slot')
                ->pluck('c', 'slot'),
            'colleges'   => $colleges,
            'programs'   => $this->programOptions(),
            'years'      => self::YEARS,
            'sections'   => self::SECTIONS,
            'total'      => AttendanceClaim::where('event_id', $event->id)->count(),
            'today'      => AttendanceClaim::where('event_id', $event->id)->whereDate('created_at', today())->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // Normalize spacing, then combine the two dropdowns into "4-A".
        $input = array_map(
            fn ($v) => is_string($v) ? preg_replace('/\s+/', ' ', trim($v)) : $v,
            $request->only(['event_id', 'first_name', 'last_name', 'year', 'section', 'college', 'program', 'slot'])
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
            'event_id'     => ['required', 'integer', 'exists:events,id'],
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'year'         => ['required', Rule::in(array_keys(self::YEARS))],
            'section'      => ['required', Rule::in(self::SECTIONS)],
            // free text: picked from the dropdown or typed in
            'college'      => ['required', 'string', 'min:2', 'max:150'],
            'program'      => ['required', 'string', 'min:2', 'max:150'],
            'slot'         => ['required', Rule::in(array_keys(AttendanceClaim::SLOTS))],
            'year_section' => ['nullable', 'string', 'max:20'],
        ]);

        // event + college + program + year & section can claim each slot only once.
        // If it was already claimed, tell the user who claimed it.
        $existing = $this->findExisting($data['event_id'], $data['college'], $data['program'], $data['year_section'], $data['slot']);
        if ($existing) {
            return $this->alreadyClaimed($existing);
        }

        try {
            AttendanceClaim::create(
                Arr::only($data, ['event_id', 'first_name', 'last_name', 'year_section', 'college', 'program', 'slot'])
            );
        } catch (QueryException $e) {
            // Two people submitting at the same moment: the unique index caught the second one.
            $existing = $this->findExisting($data['event_id'], $data['college'], $data['program'], $data['year_section'], $data['slot']);
            if ($existing) {
                return $this->alreadyClaimed($existing);
            }
            throw $e;
        }

        $message = "{$data['first_name']} {$data['last_name']}: {$slotLabel} claimed.";

        // The form submits with fetch() so it can stay open: answer with JSON.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'total'   => AttendanceClaim::where('event_id', $data['event_id'])->count(),
                'counts'  => AttendanceClaim::where('event_id', $data['event_id'])
                    ->selectRaw('slot, COUNT(*) as c')
                    ->groupBy('slot')
                    ->pluck('c', 'slot'),
            ]);
        }

        return redirect()
            ->route('claims.index', ['event' => $data['event_id']])
            ->with('success', $message);
    }

    /** Live check used by the form: has this slot already been claimed, and by whom? */
    public function check(Request $request): JsonResponse
    {
        $clean = fn ($v) => is_string($v) ? preg_replace('/\s+/', ' ', trim($v)) : null;

        $in = [
            'event'   => $clean($request->query('event')),
            'college' => $clean($request->query('college')),
            'program' => $clean($request->query('program')),
            'year'    => $clean($request->query('year')),
            'section' => $clean($request->query('section')),
            'slot'    => $clean($request->query('slot')),
        ];
        $in['college'] = $this->normalizeCollege($in['college']);

        $valid = Validator::make($in, [
            'event'   => ['required', 'integer', 'exists:events,id'],
            'college' => ['required', 'string', 'min:2', 'max:150'],
            'program' => ['required', 'string', 'min:2', 'max:150'],
            'year'    => ['required', Rule::in(array_keys(self::YEARS))],
            'section' => ['required', Rule::in(self::SECTIONS)],
            'slot'    => ['required', Rule::in(array_keys(AttendanceClaim::SLOTS))],
        ]);

        if ($valid->fails()) {
            return response()->json(['claimed' => false]);
        }

        $claim = $this->findExisting($in['event'], $in['college'], $in['program'], $in['year'] . '-' . $in['section'], $in['slot']);

        return response()->json($claim ? $this->claimedInfo($claim) : ['claimed' => false]);
    }

    private function findExisting(int|string $eventId, string $college, string $program, string $yearSection, string $slot): ?AttendanceClaim
    {
        return AttendanceClaim::where('event_id', $eventId)
            ->where('college', $college)
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

    private function alreadyClaimed(AttendanceClaim $claim): RedirectResponse|JsonResponse
    {
        $i = $this->claimedInfo($claim);
        $msg = "{$i['slot']} for {$i['group']} was already claimed by {$i['name']} on {$i['when']}.";

        if (request()->expectsJson()) {
            return response()->json(['message' => $msg, 'errors' => ['year_section' => [$msg]]], 422);
        }

        return back()->withInput()->withErrors(['year_section' => $msg]);
    }

    public function destroy(AttendanceClaim $claim): RedirectResponse
    {
        $claim->delete();

        return back()->with('success', 'Claim removed.');
    }

    /**
     * Download ALL claims of one event as an .xlsx file.
     * Sheet 1 = every claim; then one sheet per slot (Morning Login, Morning Logout, ...).
     */
    public function exportEvent(Event $event): StreamedResponse
    {
        $claims = $event->claims()
            ->orderBy('year_section')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();

        $headers = ['No.', 'Last name', 'First name', 'Year & Section', 'College', 'Program', 'Slot', 'Claimed at'];

        $sheets = ['All claims' => $claims];
        foreach (AttendanceClaim::SLOTS as $key => $label) {
            $sheets[$label] = $claims->where('slot', $key)->values();
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setTitle($event->name)->setSubject('SSC Attendance Claim');

        $index = 0;
        foreach ($sheets as $title => $rows) {
            $sheet = $index++ === 0 ? $spreadsheet->getActiveSheet() : $spreadsheet->createSheet();
            $sheet->setTitle($title);

            // Header row
            foreach ($headers as $col => $text) {
                $sheet->setCellValueExplicit([$col + 1, 1], $text, DataType::TYPE_STRING);
            }

            // Data rows. Everything is written as plain text so a name that starts
            // with "=" can never turn into a formula.
            foreach ($rows as $i => $c) {
                $values = [
                    (string) ($i + 1),
                    $c->last_name,
                    $c->first_name,
                    $c->year_section,
                    $c->college,
                    $c->program,
                    $c->slot_label,
                    $c->created_at->format('M d, Y h:i A'),
                ];

                foreach ($values as $col => $value) {
                    $sheet->setCellValueExplicit([$col + 1, $i + 2], (string) $value, DataType::TYPE_STRING);
                }
            }

            $lastCol = Coordinate::stringFromColumnIndex(count($headers));
            $lastRow = max(1, $rows->count() + 1);

            $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '6366F1']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A1:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->freezePane('A2');
            $sheet->setAutoFilter("A1:{$lastCol}{$lastRow}");

            foreach (range(1, count($headers)) as $col) {
                $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = Str::slug($event->name) . '-' . now()->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function storeEvent(Request $request): RedirectResponse
    {
        $request->merge(['name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name')))]);

        $data = $request->validateWithBag('event', [
            'name' => ['required', 'string', 'min:2', 'max:150', 'unique:events,name'],
        ]);

        $event = Event::create($data);

        return redirect()
            ->route('claims.index', ['event' => $event->id])
            ->with('success', "Event “{$event->name}” created.");
    }

    public function destroyEvent(Request $request, Event $event): RedirectResponse
    {
        // Safety net on the server too: the form must carry the typed word DELETE.
        if (strtoupper(trim((string) $request->input('confirm'))) !== 'DELETE') {
            return back()->withErrors(['confirm' => 'Type DELETE to confirm.'], 'deleteEvent');
        }

        $name  = $event->name;
        $count = $event->claims()->count();

        $event->delete(); // its claims are removed by the cascade

        return redirect()
            ->route('claims.index')
            ->with('success', "Event “{$name}” and its {$count} " . Str::plural('claim', $count) . ' were deleted.');
    }
}
