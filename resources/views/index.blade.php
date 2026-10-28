<x-layout>
    @php
        // ---- Tailwind class tokens reused across the page -------------------------------------------
        $btnBase    = 'items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-60';
        $btnPrimary = $btnBase . ' bg-[linear-gradient(135deg,#6366f1,#8b5cf6)] text-white shadow-lg shadow-indigo-500/30 hover:brightness-110';
        $btnGhost   = $btnBase . ' border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
        $btnDanger  = $btnBase . ' bg-red-500 text-white hover:bg-red-600';

        $card       = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';

        $fieldBase  = 'w-full rounded-xl border border-transparent bg-slate-100 text-slate-900 placeholder-slate-400 transition hover:border-slate-200 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-700 dark:focus:bg-slate-900';
        $inpLg      = $fieldBase . ' px-3.5 py-3 text-base';
        $selLg      = $fieldBase . ' appearance-none py-3 pl-3.5 pr-10 text-base';
        $selSm      = $fieldBase . ' appearance-none py-2.5 pl-3 pr-9 text-sm truncate';
        $comboInp   = $fieldBase . ' py-3 pl-3.5 pr-11 text-base';

        $label      = 'mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300';
        $fLabel     = 'mb-1 block text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400';
        $errText    = 'mt-1.5 text-xs text-red-500';

        $chev = '<svg class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>';

        $chipOn     = 'border-transparent bg-[linear-gradient(135deg,#6366f1,#8b5cf6)] text-white shadow-md shadow-indigo-500/30';
        $chipOff    = 'border-slate-200 bg-white text-slate-500 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-slate-100';
        $chipNumOn  = 'bg-white/25 text-white';
        $chipNumOff = 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400';

        $slotOn     = 'border-indigo-500 bg-indigo-50 ring-4 ring-indigo-500/15 dark:bg-indigo-500/10';
        $slotOff    = 'border-transparent bg-slate-100 hover:border-slate-200 dark:bg-slate-800 dark:hover:border-slate-700';
        $icoOn      = 'bg-[linear-gradient(135deg,#6366f1,#8b5cf6)] text-white';
        $icoOff     = 'bg-white text-slate-500 dark:bg-slate-900 dark:text-slate-400';

        $pillIn     = 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400';
        $pillOut    = 'bg-amber-500/10 text-amber-600 dark:text-amber-400';
    @endphp
    {{-- soft background glow --}}
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -right-24 -top-32 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl"></div>
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-5xl px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6 sm:pb-16 sm:pt-10"
         x-data="claims({ hasErrors: @js($errors->any()), eventModal: @js($errors->event->any()), eventId: @js($event->id), counts: @js($slotCounts), total: @js($total), q: @js($search), slot: @js($slot ?? ''), filters: @js($filters), programs: @js($programs) })"
         x-effect="document.documentElement.classList.toggle('overflow-hidden', modal || eventModal || !!deleteUrl)"
         @claim-saved="saved($event.detail)"
         @keydown.escape.window="modal = false; eventModal = false; deleteUrl = null">

        {{-- Toast --}}
        @if (session('success'))
            <div class="fixed left-1/2 top-[max(1rem,env(safe-area-inset-top))] z-[60] flex max-w-[calc(100vw-1.75rem)] -translate-x-1/2 items-center gap-2.5 rounded-2xl border border-emerald-500/40 bg-white px-4 py-3 text-sm font-medium shadow-2xl dark:bg-slate-900"
                 role="status" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3800)"
                 x-show="show" x-cloak x-transition.opacity.duration.300ms>
                <svg class="h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.58 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Success toast for claims saved from the open form --}}
        <div class="fixed left-1/2 top-[max(1rem,env(safe-area-inset-top))] z-[70] flex max-w-[calc(100vw-1.75rem)] -translate-x-1/2 items-center gap-2.5 rounded-2xl border border-emerald-500/40 bg-white px-4 py-3 text-sm font-medium shadow-2xl dark:bg-slate-900"
             role="status" x-show="toast.show" x-cloak x-transition.opacity.duration.300ms>
            <svg class="h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.58 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
            <span x-text="toast.msg"></span>
        </div>

        {{-- Header --}}
        <header class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h1 class="mt-1 text-3xl font-bold leading-tight tracking-tight sm:text-4xl sm:text-center">SSC Attendance Claim</h1>
            </div>
            <div class="flex items-center gap-2.5">
                <button type="button" class="{{ $btnPrimary }} hidden sm:inline-flex" @click="modal = true">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                    New claim
                </button>
            </div>
        </header>

        {{-- Events dropdown: every event has its own morning/afternoon login & logout --}}
        <div class="mb-3 flex flex-wrap items-center gap-2" role="group" aria-label="Events">
            <div class="relative min-w-0 flex-1 sm:max-w-sm sm:flex-none sm:basis-80"
                 x-data="{ eOpen: false }" @click.outside="eOpen = false" @keydown.escape="eOpen = false">
                <button type="button" @click="eOpen = !eOpen" aria-haspopup="listbox" :aria-expanded="eOpen" aria-label="Choose event"
                        class="{{ $btnGhost }} flex w-full !justify-between !py-3 text-left">
                    <span class="flex min-w-0 items-center gap-2">
                        <svg class="h-[18px] w-[18px] shrink-0 text-indigo-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4.5" width="14" height="12" rx="2"/><path d="M3 8.5h14M7 3v3M13 3v3"/></svg>
                        <span class="truncate">{{ $event->name }}</span>
                    </span>
                    <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200" :class="eOpen ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
                </button>

                <ul role="listbox" x-show="eOpen" x-cloak x-transition.opacity.duration.120ms
                    class="absolute inset-x-0 top-full z-30 mt-1.5 max-h-72 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($events as $e)
                        <li role="option" aria-selected="{{ $e->id === $event->id ? 'true' : 'false' }}">
                            <a href="{{ route('claims.index', ['event' => $e->id]) }}"
                               class="flex w-full items-center justify-between gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800 {{ $e->id === $event->id ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '' }}">
                                <span class="truncate">{{ $e->name }}</span>
                                @if ($e->id === $event->id)
                                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7"/></svg>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <button type="button" @click="eventModal = true" class="{{ $btnGhost }} inline-flex shrink-0 !py-3" aria-label="Add event">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                <span class="hidden sm:inline">Event</span>
            </button>

            <a href="{{ route('claims.events.export', $event) }}" aria-label="Export {{ $event->name }} to Excel"
               class="{{ $btnGhost }} inline-flex shrink-0 !py-3">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3v9m0 0-3.5-3.5M10 12l3.5-3.5M4 14.5V16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-1.5"/></svg>
                <span class="hidden sm:inline">Export Excel</span>
            </a>

            <button type="button" aria-label="Delete event" @click="$dispatch('confirm-delete-event')"
                    class="{{ $btnGhost }} inline-flex shrink-0 !py-3 !text-red-500 hover:!bg-red-500/10">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h12M8 6V4h4v2M6 6l.7 9.3a1 1 0 0 0 1 .7h4.6a1 1 0 0 0 1-.7L14 6"/></svg>
                <span class="hidden sm:inline">Delete event</span>
            </button>
        </div>

        {{-- Search (live, no reload) + mobile filter toggle --}}
        <div class="mb-3 flex gap-2.5">
            <form class="relative min-w-0 flex-1" method="GET" action="{{ route('claims.index') }}" x-ref="searchForm" @submit.prevent="search()" role="search">
                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="9" r="5.5"/><path d="m13.5 13.5 3 3"/></svg>
                <input type="search" name="q" x-ref="q" x-model="q" @input.debounce.350ms="search()"
                       placeholder="Search name, program, section or college" autocomplete="off" aria-label="Search claims"
                       class="w-full rounded-2xl border border-slate-200 bg-white py-3.5 pl-11 pr-11 text-base shadow-sm placeholder-slate-400 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/15 dark:border-slate-800 dark:bg-slate-900 [&::-webkit-search-cancel-button]:hidden">
                <button type="button" x-show="q" x-cloak @click="clear()" aria-label="Clear search"
                        class="absolute right-2.5 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m3 3 10 10M13 3 3 13"/></svg>
                </button>
            </form>

            <button type="button" class="{{ $btnGhost }} inline-flex shrink-0 rounded-2xl px-3.5 shadow-sm sm:hidden"
                    @click="showFilters = !showFilters" :aria-expanded="showFilters" aria-controls="filters" aria-label="Filters">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4.5h14l-5.3 6.2V16l-3.4 1.5v-6.8L3 4.5Z"/></svg>
                <span x-show="activeFilters" x-cloak x-text="activeFilters"
                      class="grid h-[18px] min-w-[18px] place-items-center rounded-full bg-indigo-600 px-1 text-[11px] font-bold text-white"></span>
            </button>
        </div>

        {{-- Filters: college / program / year / section --}}
        <section id="filters" aria-label="Filters"
                 class="{{ $card }} mb-3 grid-cols-1 gap-3 p-3.5 min-[381px]:grid-cols-2 sm:grid sm:grid-cols-4"
                 :class="showFilters ? 'grid' : 'hidden'">
            <div>
                <label for="f-college" class="{{ $fLabel }}">College</label>
                <div class="relative">
                    <select id="f-college" class="{{ $selSm }}" x-model="f.college" @change="onFilterCollege()">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c)
                            <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                    {!! $chev !!}
                </div>
            </div>
            <div>
                <label for="f-program" class="{{ $fLabel }}">Program</label>
                <div class="relative">
                    <select id="f-program" class="{{ $selSm }}" :disabled="!f.college" @change="f.program = $event.target.value; search()">
                        <option value="" :selected="!f.program" x-text="f.college ? 'All programs' : 'Select a college first'">All programs</option>
                        <template x-for="p in programChoices" :key="p">
                            <option :value="p" :selected="p === f.program" x-text="p"></option>
                        </template>
                    </select>
                    {!! $chev !!}
                </div>
            </div>
            <div>
                <label for="f-year" class="{{ $fLabel }}">Year</label>
                <div class="relative">
                    <select id="f-year" class="{{ $selSm }}" x-model="f.year" @change="search()">
                        <option value="">All years</option>
                        @foreach ($years as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    {!! $chev !!}
                </div>
            </div>
            <div>
                <label for="f-section" class="{{ $fLabel }}">Section</label>
                <div class="relative">
                    <select id="f-section" class="{{ $selSm }}" x-model="f.section" @change="search()">
                        <option value="">All sections</option>
                        @foreach ($sections as $sec)
                            <option value="{{ $sec }}">Section {{ $sec }}</option>
                        @endforeach
                    </select>
                    {!! $chev !!}
                </div>
            </div>
            <div class="col-span-full flex justify-end" x-show="activeFilters" x-cloak>
                <button type="button" class="{{ $btnGhost }} inline-flex !px-3.5 !py-2 text-[13px]" @click="clearFilters()">Clear filters</button>
            </div>
        </section>

        {{-- Slot filter chips (counts are for the current event) --}}
        @php $base = array_filter(['event' => $event->id, 'q' => $search] + $filters); @endphp
        <nav class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-5" aria-label="Filter by attendance slot">
            <a href="{{ route('claims.index', $base) }}"
               class="col-span-2 flex items-center justify-between gap-2 rounded-2xl border px-3.5 py-2.5 text-sm font-semibold transition sm:col-span-1"
               :class="slot === '' ? @js($chipOn) : @js($chipOff)" @click.prevent="setSlot('')">
                <span>All</span>
                <i class="rounded-full px-2 py-px text-[11px] not-italic tabular-nums" x-text="total" :class="slot === '' ? @js($chipNumOn) : @js($chipNumOff)">{{ $total }}</i>
            </a>
            @foreach ($slots as $key => $label)
                @php [$chipSession, $chipType] = explode('_', $key); @endphp
                <a href="{{ route('claims.index', $base + ['slot' => $key]) }}"
                   class="flex items-center justify-between gap-2 rounded-2xl border px-3.5 py-2 transition"
                   :class="slot === @js($key) ? @js($chipOn) : @js($chipOff)" @click.prevent="setSlot(@js($key))">
                    <span class="min-w-0 leading-tight">
                        <small class="block text-[11px] font-medium opacity-70">{{ ucfirst($chipSession) }}</small>
                        <b class="block text-sm font-semibold">{{ ucfirst($chipType) }}</b>
                    </span>
                    <i class="rounded-full px-2 py-px text-[11px] not-italic tabular-nums" x-text="counts[@js($key)] ?? 0" :class="slot === @js($key) ? @js($chipNumOn) : @js($chipNumOff)">{{ $slotCounts[$key] ?? 0 }}</i>
                </a>
            @endforeach
        </nav>

        {{-- Results --}}
        <section class="{{ $card }} relative overflow-hidden" aria-live="polite">
            <div class="absolute inset-x-0 top-0 z-10 h-0.5 animate-pulse bg-[linear-gradient(90deg,transparent,#6366f1,#8b5cf6,transparent)]" x-show="loading" x-cloak></div>

            <div x-ref="results" data-results @click="page($event)" class="transition-opacity" :class="loading ? 'opacity-50' : ''">
                @php
                    $bits = array_values(array_filter([
                        $slot ? $slots[$slot] : null,
                        $filters['college'] ?: null,
                        $filters['program'] ?: null,
                        $filters['year'] ? $years[$filters['year']] : null,
                        $filters['section'] ? 'Section ' . $filters['section'] : null,
                    ]));
                @endphp
                @if ($search !== '' || count($bits))
                    <div class="border-b border-slate-100 px-5 py-3.5 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">Results
                        @if ($search !== '') for <b class="font-semibold text-slate-900 dark:text-slate-100">“{{ $search }}”</b>@endif
                        @foreach ($bits as $bit) · <b class="font-semibold text-slate-900 dark:text-slate-100">{{ $bit }}</b>@endforeach
                    </div>
                @endif

                @if ($claims->count())
                    <div class="hidden grid-cols-[minmax(0,2fr)_84px_minmax(0,1.9fr)_150px_150px_40px] items-center gap-3 bg-slate-50 px-5 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:bg-slate-800/50 dark:text-slate-400 md:grid" aria-hidden="true">
                        <div>Name</div><div>Section</div><div>Program &amp; College</div><div>Slot</div><div>Claimed</div><div></div>
                    </div>

                    @foreach ($claims as $claim)
                        @php
                            $hue = crc32($claim->last_name . $claim->first_name) % 360;
                            $initials = \Illuminate\Support\Str::upper(
                                \Illuminate\Support\Str::substr($claim->first_name, 0, 1) . \Illuminate\Support\Str::substr($claim->last_name, 0, 1)
                            );
                            $full = $claim->first_name . ' ' . $claim->last_name;
                        @endphp
                        <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2.5 border-t border-slate-100 px-4 py-4 transition first:border-t-0 hover:bg-indigo-500/5 dark:border-slate-800 md:grid-cols-[minmax(0,2fr)_84px_minmax(0,1.9fr)_150px_150px_40px] md:gap-3 md:px-5 md:py-3.5">
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[hsl(var(--h)_80%_55%/0.16)] text-sm font-bold text-[hsl(var(--h)_65%_40%)] dark:text-[hsl(var(--h)_70%_72%)]"
                                     style="--h: {{ $hue }}">{{ $initials }}</div>
                                <div class="truncate font-semibold">{{ $claim->last_name }}, {{ $claim->first_name }}</div>
                            </div>

                            <div class="col-span-2 row-start-2 flex flex-wrap items-center gap-x-2.5 gap-y-1.5 pl-[52px] md:contents">
                                <span class="inline-flex items-center justify-self-start whitespace-nowrap rounded-full bg-indigo-500/10 px-2.5 py-1 text-xs font-semibold text-indigo-600 dark:text-indigo-300">{{ $claim->year_section }}</span>
                                <span class="order-first block min-w-0 basis-full md:order-none md:basis-auto" title="{{ $claim->program }} · {{ $claim->college }}">
                                <span class="block truncate text-sm font-medium">{{ $claim->program }}</span>
                                <span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ $claim->college }}</span>
                            </span>
                                <span class="inline-flex items-center gap-1.5 justify-self-start whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold {{ $claim->slot_type === 'out' ? $pillOut : $pillIn }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $claim->slot_label }}
                            </span>
                                <span class="basis-full truncate text-sm text-slate-500 dark:text-slate-400 md:basis-auto" title="{{ $claim->created_at->format('M d, Y h:i A') }}">{{ $claim->created_at->format('M d, Y · h:i A') }}</span>
                            </div>

                            <button type="button" aria-label="Remove {{ $full }}"
                                    class="col-start-2 row-start-1 grid h-9 w-9 place-items-center rounded-xl text-slate-400 transition hover:bg-red-500/10 hover:text-red-500 md:col-start-auto md:row-start-auto"
                                    @click="askDelete(@js(route('claims.destroy', $claim)), @js($full))">
                                <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h12M8 6V4h4v2M6 6l.7 9.3a1 1 0 0 0 1 .7h4.6a1 1 0 0 0 1-.7L14 6"/></svg>
                            </button>
                        </div>
                    @endforeach

                    @if ($claims->previousPageUrl() || $claims->nextPageUrl())
                        <div class="flex justify-between gap-2.5 border-t border-slate-100 px-5 py-3.5 dark:border-slate-800">
                            @if ($claims->previousPageUrl())
                                <a class="{{ $btnGhost }} inline-flex" data-page href="{{ $claims->previousPageUrl() }}">&larr; Previous</a>
                            @else <span></span> @endif
                            @if ($claims->nextPageUrl())
                                <a class="{{ $btnGhost }} inline-flex" data-page href="{{ $claims->nextPageUrl() }}">Next &rarr;</a>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="px-5 py-14 text-center text-slate-500 dark:text-slate-400">
                        <div class="mx-auto mb-3.5 grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-indigo-500 dark:bg-slate-800">
                            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/></svg>
                        </div>
                        <h3 class="mb-1 text-base font-semibold text-slate-900 dark:text-slate-100">{{ ($search !== '' || count($bits)) ? 'No claim found' : 'No claims yet' }}</h3>
                        <p class="text-sm">{{ ($search !== '' || count($bits)) ? 'Try a different search or adjust the filters.' : 'Tap “New claim” to add the first one.' }}</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Mobile FAB --}}
        <button type="button" aria-label="New claim" @click="modal = true"
                class="{{ $btnPrimary }} fixed bottom-[max(1.1rem,env(safe-area-inset-bottom))] right-[max(1.1rem,env(safe-area-inset-right))] z-40 inline-flex rounded-2xl px-5 py-3.5 shadow-xl sm:hidden">
            <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
            New claim
        </button>

        {{-- New claim modal / bottom sheet --}}
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 backdrop-blur-sm sm:items-center"
             x-show="modal" x-cloak x-transition.opacity @click.self="modal = false"
             x-effect="if (modal) $nextTick(() => { const f = document.getElementById('first_name'); if (f) f.focus(); })">
            <div class="max-h-[92dvh] w-full max-w-lg overflow-y-auto rounded-t-3xl border border-slate-200 bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl dark:border-slate-800 dark:bg-slate-900 sm:rounded-3xl sm:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="claim-title"
                 x-show="modal"
                 x-transition:enter="transition duration-300 ease-out"
                 x-transition:enter-start="translate-y-6 opacity-0 sm:translate-y-3 sm:scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                 x-transition:leave-end="translate-y-6 opacity-0 sm:translate-y-3 sm:scale-95">

                <div class="mx-auto -mt-2 mb-3.5 h-1 w-10 rounded-full bg-slate-200 dark:bg-slate-700 sm:hidden"></div>

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="claim-title" class="text-xl font-bold tracking-tight">Claim attendance</h2>
                        <p class="mb-4 mt-1 text-sm text-slate-500 dark:text-slate-400">
                            <span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ $event->name }}</span> · Fill in the details below.
                        </p>
                    </div>
                    <button type="button" @click="modal = false" aria-label="Close"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500 transition hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700">
                        <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m3 3 10 10M13 3 3 13"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('claims.store') }}" @submit.prevent="submit($event)"
                      x-data="claimForm({ eventId: @js($event->id), college: @js(old('college', '')), program: @js(old('program', '')), year: @js(old('year', '')), section: @js(old('section', '')), slot: @js(old('slot', '')), programs: @js($programs), serverMsg: @js($errors->has('year_section')), url: @js(route('claims.check')) })">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ $event->id }}">
                    <div class="grid grid-cols-1 gap-3.5 min-[381px]:grid-cols-2">

                        {{-- Slot: dropdown --}}
                        <div class="col-span-full">
                            <label for="slot" class="{{ $label }}">Attendance slot</label>
                            <div class="relative"
                                 @click.outside="sOpen = false"
                                 @keydown.escape="sOpen = false"
                                 x-data="{
             sOpen: false,
             slots: @js(collect($slots)->map(fn ($l, $k) => ['value' => $k, 'label' => $l, 'session' => explode('_', $k)[0]])->values()),
             get sLabel() {
                 const m = this.slots.find(x => x.value === claimSlot);
                 return m ? m.label : '';
             },
             sPick(v) { claimSlot = v; this.sOpen = false; }
         }">
                                <input type="hidden" name="slot" :value="claimSlot">

                                <button type="button" id="slot" @click="sOpen = !sOpen"
                                        aria-haspopup="listbox" :aria-expanded="sOpen"
                                        class="{{ $selLg }} text-left">
                                    <span x-text="sLabel || 'Select attendance slot'" :class="sLabel ? '' : 'text-slate-400'"></span>
                                </button>
                                {!! $chev !!}

                                <ul role="listbox" x-show="sOpen" x-cloak x-transition.opacity.duration.120ms
                                    class="absolute inset-x-0 top-full z-20 mt-1.5 max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                    <template x-for="sl in slots" :key="sl.value">
                                        <li role="option" :aria-selected="claimSlot === sl.value">
                                            <button type="button" @click="sPick(sl.value)"
                                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    :class="claimSlot === sl.value ? 'font-semibold text-indigo-600 dark:text-indigo-400' : ''">
                                                <svg x-show="sl.session === 'morning'" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="10" cy="10" r="3.2"/><path d="M10 2.5v1.8M10 15.7v1.8M2.5 10h1.8M15.7 10h1.8M4.7 4.7 6 6M14 14l1.3 1.3M4.7 15.3 6 14M14 6l1.3-1.3"/></svg>
                                                <svg x-show="sl.session !== 'morning'" class="h-[18px] w-[18px] shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5.5 13a4.5 4.5 0 0 1 9 0M2.5 13h15M10 3.5v2M4 6l1.3 1.3M16 6l-1.3 1.3M5 16.5h10"/></svg>
                                                <span class="flex-1" x-text="sl.label"></span>
                                                <svg x-show="claimSlot === sl.value" class="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7"/></svg>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            @error('slot') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            @error('event_id') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.slot" x-cloak x-text="errors.slot ? errors.slot[0] : ''"></div>
                            <div class="{{ $errText }}" x-show="errors.event_id" x-cloak x-text="errors.event_id ? errors.event_id[0] : ''"></div>
                        </div>

                        <div>
                            <label for="first_name" class="{{ $label }}">First name</label>
                            <input id="first_name" name="first_name" value="{{ old('first_name') }}" autocomplete="given-name" required class="{{ $inpLg }} uppercase">
                            @error('first_name') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.first_name" x-cloak x-text="errors.first_name ? errors.first_name[0] : ''"></div>
                        </div>
                        <div>
                            <label for="last_name" class="{{ $label }}">Last name</label>
                            <input id="last_name" name="last_name" value="{{ old('last_name') }}" autocomplete="family-name" required class="{{ $inpLg }} uppercase">
                            @error('last_name') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.last_name" x-cloak x-text="errors.last_name ? errors.last_name[0] : ''"></div>
                        </div>

                        {{-- College: dropdown --}}
                        <div class="col-span-full">
                            <label for="college" class="{{ $label }}">College</label>
                            <div class="relative" @click.outside="cOpen = false"
                                 x-data="{
             cOpen: false,
             cActive: -1,
             colleges: @js($colleges),
             cSame(c) { return c.toLowerCase() === (college || '').trim().toLowerCase(); },
             cMove(d) {
                 if (!this.cOpen) { this.cOpen = true; return; }
                 const n = this.colleges.length;
                 if (!n) return;
                 this.cActive = this.cActive < 0 ? (d > 0 ? 0 : n - 1) : (this.cActive + d + n) % n;
             },
             cPick(c) {
                 college = c;
                 this.cOpen = false;
                 this.cActive = -1;
                 this.$nextTick(() => onCollege());
             }
         }">
                                <input type="hidden" name="college" :value="college">

                                <button type="button" id="college" @click="cOpen = !cOpen"
                                        role="combobox" aria-haspopup="listbox" aria-controls="college-list" :aria-expanded="cOpen"
                                        @keydown.arrow-down.prevent="cMove(1)" @keydown.arrow-up.prevent="cMove(-1)"
                                        @keydown.enter="if (cOpen && cActive >= 0) { $event.preventDefault(); cPick(colleges[cActive]); }"
                                        @keydown.escape="if (cOpen) { cOpen = false; $event.stopPropagation(); }"
                                        class="{{ $comboInp }} text-left uppercase">
                                    <span class="block truncate" x-text="college || 'Select College'" :class="college ? '' : 'text-slate-400'"></span>
                                </button>
                                <span class="pointer-events-none absolute right-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center text-slate-400">
            <svg class="h-4 w-4 transition-transform duration-200" :class="cOpen ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>

                                <ul id="college-list" role="listbox" x-show="cOpen" x-cloak x-transition.opacity.duration.120ms
                                    class="absolute inset-x-0 bottom-full z-20 mb-1.5 max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                    <template x-for="(c, i) in colleges" :key="c">
                                        <li role="option" :aria-selected="cSame(c)">
                                            <button type="button"
                                                    class="flex w-full items-center justify-between gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm"
                                                    :class="[cActive === i ? 'bg-slate-100 dark:bg-slate-800' : '', cSame(c) ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '']"
                                                    @mouseenter="cActive = i" @click="cPick(c)">
                                                <span x-text="c"></span>
                                                <svg x-show="cSame(c)" class="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7"/></svg>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            @error('college') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.college" x-cloak x-text="errors.college ? errors.college[0] : ''"></div>
                        </div>

                        {{-- Program: dropdown --}}
                        <div class="col-span-full">
                            <label for="program" class="{{ $label }}">Program</label>
                            <div class="relative" @click.outside="open = false">
                                <input type="hidden" name="program" :value="program">

                                <button type="button" id="program" :disabled="!college" @click="toggle()"
                                        role="combobox" aria-haspopup="listbox" aria-controls="program-list" :aria-expanded="open"
                                        @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
                                        @keydown.enter="if (open && active >= 0) { $event.preventDefault(); choose(); }"
                                        @keydown.escape="if (open) { open = false; $event.stopPropagation(); }"
                                        class="{{ $comboInp }} text-left uppercase disabled:cursor-not-allowed disabled:opacity-50">
            <span class="block truncate" x-text="program || (college ? 'Select your program' : 'Select a college first')"
                  :class="program ? '' : 'text-slate-400'"></span>
                                </button>
                                <span class="pointer-events-none absolute right-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center text-slate-400">
            <svg class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m4 6 4 4 4-4"/></svg>
        </span>

                                <ul id="program-list" role="listbox" x-ref="list" x-show="open && college" x-cloak x-transition.opacity.duration.120ms
                                    class="absolute inset-x-0 bottom-full z-20 mb-1.5 max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                    <template x-for="(p, i) in options" :key="p">
                                        <li role="option" :aria-selected="same(p)">
                                            <button type="button" data-opt
                                                    class="flex w-full items-center justify-between gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm"
                                                    :class="[active === i ? 'bg-slate-100 dark:bg-slate-800' : '', same(p) ? 'font-semibold text-indigo-600 dark:text-indigo-400' : '']"
                                                    @mouseenter="active = i" @click="pick(p)">
                                                <span x-text="p"></span>
                                                <svg x-show="same(p)" class="h-4 w-4 shrink-0" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3.5 8.5 3 3 6-7"/></svg>
                                            </button>
                                        </li>
                                    </template>
                                    <li class="p-3 text-center text-sm text-slate-500 dark:text-slate-400" x-show="!options.length">No programs listed for this college.</li>
                                </ul>
                            </div>
                            @error('program') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.program" x-cloak x-text="errors.program ? errors.program[0] : ''"></div>
                        </div>

                        <div>
                            <label for="year" class="{{ $label }}">Year</label>
                            <div class="relative"
                                 @click.outside="yOpen = false"
                                 @keydown.escape="yOpen = false"
                                 x-data="{
             yOpen: false,
             years: @js(collect($years)->map(fn ($l, $v) => ['value' => (string) $v, 'label' => $l])->values()),
             get yLabel() {
                 const m = this.years.find(y => y.value === String(year));
                 return m ? m.label : '';
             },
             yPick(v) { year = v; this.yOpen = false; }
         }">
                                <input type="hidden" name="year" :value="year">

                                <button type="button" id="year" @click="yOpen = !yOpen"
                                        aria-haspopup="listbox" :aria-expanded="yOpen"
                                        class="{{ $selLg }} text-left">
                                    <span x-text="yLabel || 'Select year'" :class="yLabel ? '' : 'text-slate-400'"></span>
                                </button>
                                {!! $chev !!}

                                <ul role="listbox" x-show="yOpen" x-cloak x-transition.opacity.duration.120ms
                                    class="absolute inset-x-0 bottom-full z-20 mb-1.5 max-h-60 overflow-auto rounded-2xl border border-slate-200 bg-white p-1.5 shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                                    <template x-for="y in years" :key="y.value">
                                        <li role="option" :aria-selected="String(year) === y.value">
                                            <button type="button" @click="yPick(y.value)"
                                                    class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-800"
                                                    :class="String(year) === y.value ? 'font-semibold text-indigo-600 dark:text-indigo-400' : ''">
                                                <span x-text="y.label"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                            @error('year') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.year" x-cloak x-text="errors.year ? errors.year[0] : ''"></div>
                        </div>

                        <div>
                            <label for="section" class="{{ $label }}">Section</label>
                            <div class="relative">
                                <select id="section" name="section" required x-model="section" class="{{ $selLg }}">
                                    <option value="" disabled>Select section</option>
                                    @foreach ($sections as $sec)
                                        <option value="{{ $sec }}">Section {{ $sec }}</option>
                                    @endforeach
                                </select>
                                {!! $chev !!}
                            </div>
                            @error('section') <div class="{{ $errText }}">{{ $message }}</div> @enderror
                            <div class="{{ $errText }}" x-show="errors.section" x-cloak x-text="errors.section ? errors.section[0] : ''"></div>
                        </div>
                    </div>

                    {{-- Who already claimed this slot (live check) --}}
                    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-3.5 text-sm" role="alert"
                         x-show="claimed" x-cloak x-transition.opacity>
                        <svg class="mt-px h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.2 2.6 16a1 1 0 0 0 .87 1.5h13.06A1 1 0 0 0 17.4 16L10 3.2Z"/><path d="M10 8v3.6M10 14.2h.01"/></svg>
                        <div>
                            <b class="mb-0.5 block font-semibold">Already claimed</b>
                            <p class="text-slate-600 dark:text-slate-300"><span x-text="claimed ? claimed.slot : ''"></span> for <span x-text="claimed ? claimed.group : ''"></span>
                                was claimed by <strong class="font-semibold text-slate-900 dark:text-white" x-text="claimed ? claimed.name : ''"></strong> on <span x-text="claimed ? claimed.when : ''"></span>.</p>
                        </div>
                    </div>
                    @if ($errors->has('year_section'))
                        <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-3.5 text-sm" role="alert" x-show="serverMsg && !claimed">
                            <svg class="mt-px h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.2 2.6 16a1 1 0 0 0 .87 1.5h13.06A1 1 0 0 0 17.4 16L10 3.2Z"/><path d="M10 8v3.6M10 14.2h.01"/></svg>
                            <div>
                                <b class="mb-0.5 block font-semibold">Already claimed</b>
                                <p class="text-slate-600 dark:text-slate-300">{{ $errors->first('year_section') }}</p>
                            </div>
                        </div>
                    @endif

                    {{-- Success confirmation (inside the form so it is always visible, even with the keyboard open) --}}
                    <div x-ref="successBox" class="mt-4 flex items-start gap-3 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 p-3.5 text-sm" role="status"
                         x-show="success" x-cloak x-transition.opacity>
                        <svg class="mt-px h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.7-9.3a1 1 0 0 0-1.4-1.4L9 10.58 7.7 9.3a1 1 0 0 0-1.4 1.4l2 2a1 1 0 0 0 1.4 0l4-4Z" clip-rule="evenodd"/></svg>
                        <div>
                            <b class="mb-0.5 block font-semibold">Claim saved</b>
                            <p class="text-slate-600 dark:text-slate-300" x-text="success"></p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-amber-500/40 bg-amber-500/10 p-3.5 text-sm" role="alert"
                         x-show="errors.year_section" x-cloak>
                        <svg class="mt-px h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 3.2 2.6 16a1 1 0 0 0 .87 1.5h13.06A1 1 0 0 0 17.4 16L10 3.2Z"/><path d="M10 8v3.6M10 14.2h.01"/></svg>
                        <div>
                            <b class="mb-0.5 block font-semibold">Already claimed</b>
                            <p class="text-slate-600 dark:text-slate-300" x-text="errors.year_section ? errors.year_section[0] : ''"></p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-red-500/40 bg-red-500/10 p-3.5 text-sm text-red-600 dark:text-red-400" role="alert"
                         x-show="formError" x-cloak x-text="formError"></div>

                    <div class="mt-5 flex gap-2.5">
                        <button type="button" class="{{ $btnGhost }} inline-flex flex-1"
                                @click="
            modal = false;
            $el.closest('form').querySelectorAll('#first_name, #last_name').forEach(i => i.value = '');
            college = '';
            program = '';
            year = '';
            section = '';
            claimSlot = '';
            claimed = null;
            serverMsg = false;
            errors = {};
            formError = '';
            success = '';
            busy = false;
        ">Close</button>
                        <button type="submit" class="{{ $btnPrimary }} inline-flex flex-1" :disabled="busy || !!claimed">
                            <span x-text="busy ? 'Submitting…' : (claimed ? 'Already claimed' : 'Submit claim')">Submit claim</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- New event modal --}}
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 backdrop-blur-sm sm:items-center"
             x-show="eventModal" x-cloak x-transition.opacity @click.self="eventModal = false"
             x-effect="if (eventModal) $nextTick(() => { const f = document.getElementById('event_name'); if (f) f.focus(); })">
            <div class="m-4 w-full max-w-sm self-center rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 role="dialog" aria-modal="true" aria-labelledby="event-title" x-show="eventModal" x-transition>
                <h2 id="event-title" class="text-xl font-bold tracking-tight">New event</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Each event has its own morning and afternoon login and logout.</p>

                <form method="POST" action="{{ route('claims.events.store') }}" class="mt-4">
                    @csrf
                    <label for="event_name" class="{{ $label }}">Event name</label>
                    <input id="event_name" name="name" value="{{ $errors->event->any() ? old('name') : '' }}" required maxlength="150"
                           placeholder="e.g. Intramurals – Day 2" autocomplete="off" class="{{ $inpLg }}">
                    @if ($errors->event->has('name'))
                        <div class="{{ $errText }}">{{ $errors->event->first('name') }}</div>
                    @endif
                    <div class="mt-5 flex gap-2.5">
                        <button type="button" class="{{ $btnGhost }} inline-flex flex-1" @click="eventModal = false">Cancel</button>
                        <button type="submit" class="{{ $btnPrimary }} inline-flex flex-1">Create event</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete confirmation --}}
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 backdrop-blur-sm sm:items-center"
             x-show="deleteUrl" x-cloak x-transition.opacity @click.self="deleteUrl = null">
            <div class="m-4 w-full max-w-sm self-center rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 role="alertdialog" aria-modal="true" aria-labelledby="del-title"
                 x-show="deleteUrl"
                 x-transition:enter="transition duration-300 ease-out"
                 x-transition:enter-start="translate-y-4 opacity-0 scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 scale-100"
                 x-transition:leave="transition duration-200 ease-in"
                 x-transition:leave-start="translate-y-0 opacity-100 scale-100"
                 x-transition:leave-end="translate-y-4 opacity-0 scale-95">
                <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-red-500/10 text-red-500">
                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h12M8 6V4h4v2M6 6l.7 9.3a1 1 0 0 0 1 .7h4.6a1 1 0 0 0 1-.7L14 6"/></svg>
                </div>
                <h2 id="del-title" class="text-xl font-bold tracking-tight">Remove this claim?</h2>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">This removes <strong class="font-semibold text-slate-900 dark:text-slate-100" x-text="deleteName"></strong>’s claim. They will be able to claim again.</p>
                <form method="POST" :action="deleteUrl">
                    @csrf
                    @method('DELETE')
                    <div class="mt-5 flex gap-2.5">
                        <button type="button" class="{{ $btnGhost }} inline-flex flex-1" @click="deleteUrl = null">Cancel</button>
                        <button type="submit" class="{{ $btnDanger }} inline-flex flex-1">Remove</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Delete EVENT confirmation: must type DELETE --}}
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/55 backdrop-blur-sm sm:items-center"
             x-data="{ open: @js($errors->deleteEvent->any()), text: '' }"
             @confirm-delete-event.window="open = true; text = ''"
             @keydown.escape.window="open = false"
             x-show="open" x-cloak x-transition.opacity @click.self="open = false"
             x-effect="if (open) $nextTick(() => { const f = document.getElementById('confirm_delete'); if (f) f.focus(); })">
            <div class="m-4 w-full max-w-sm self-center rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 role="alertdialog" aria-modal="true" aria-labelledby="delevent-title" x-show="open" x-transition>
                <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-red-500/10 text-red-500">
                    <svg class="h-6 w-6" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h12M8 6V4h4v2M6 6l.7 9.3a1 1 0 0 0 1 .7h4.6a1 1 0 0 0 1-.7L14 6"/></svg>
                </div>
                <h2 id="delevent-title" class="text-xl font-bold tracking-tight">Delete this event?</h2>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
                    This permanently deletes <strong class="font-semibold text-slate-900 dark:text-slate-100">{{ $event->name }}</strong>
                    and its <strong class="font-semibold text-slate-900 dark:text-slate-100"><span x-text="total">{{ number_format($total) }}</span> <span x-text="Number(total) === 1 ? 'claim' : 'claims'">{{ \Illuminate\Support\Str::plural('claim', $total) }}</span></strong>. This cannot be undone.
                </p>
                <p class="mt-2 text-sm">
                    <a href="{{ route('claims.events.export', $event) }}" class="font-semibold text-indigo-600 underline-offset-2 hover:underline dark:text-indigo-400">Export to Excel first</a>
                </p>

                <form method="POST" action="{{ route('claims.events.destroy', $event) }}" class="mt-4 text-left">
                    @csrf
                    @method('DELETE')
                    <label for="confirm_delete" class="{{ $label }}">Type <b class="font-bold text-red-500">DELETE</b> to confirm</label>
                    <input id="confirm_delete" name="confirm" x-model="text" autocomplete="off" autocapitalize="characters" spellcheck="false"
                           class="{{ $inpLg }} text-center tracking-widest">
                    @if ($errors->deleteEvent->has('confirm'))
                        <div class="{{ $errText }}">{{ $errors->deleteEvent->first('confirm') }}</div>
                    @endif
                    <div class="mt-5 flex gap-2.5">
                        <button type="button" class="{{ $btnGhost }} inline-flex flex-1" @click="open = false">Cancel</button>
                        <button type="submit" class="{{ $btnDanger }} inline-flex flex-1"
                                :disabled="text.trim().toUpperCase() !== 'DELETE'">Delete event</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-layout>
