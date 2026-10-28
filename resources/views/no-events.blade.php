<x-layout>
    @php
        $btnBase    = 'items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition active:scale-[0.97] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 disabled:cursor-not-allowed disabled:opacity-60';
        $btnPrimary = $btnBase . ' bg-[linear-gradient(135deg,#6366f1,#8b5cf6)] text-white shadow-lg shadow-indigo-500/30 hover:brightness-110';
        $btnGhost   = $btnBase . ' border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800';
        $card       = 'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900';
        $inpLg      = 'w-full rounded-xl border border-transparent bg-slate-100 px-3.5 py-3 text-base text-slate-900 placeholder-slate-400 transition hover:border-slate-200 focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-500/15 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-slate-700 dark:focus:bg-slate-900';
        $label      = 'mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300';
        $errText    = 'mt-1.5 text-xs text-red-500';
    @endphp

    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
        <div class="absolute -right-24 -top-32 h-96 w-96 rounded-full bg-indigo-500/15 blur-3xl"></div>
        <div class="absolute -left-24 top-10 h-80 w-80 rounded-full bg-violet-500/10 blur-3xl"></div>
    </div>

    <div class="mx-auto max-w-5xl px-4 pb-16 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6 sm:pt-10"
         x-data="{ eventModal: @js($errors->event->any()) }"
         @keydown.escape.window="eventModal = false">

        @if (session('success'))
            <div class="fixed left-1/2 top-[max(1rem,env(safe-area-inset-top))] z-[60] flex max-w-[calc(100vw-1.75rem)] -translate-x-1/2 items-center gap-2.5 rounded-2xl border border-emerald-500/40 bg-white px-4 py-3 text-sm font-medium shadow-2xl dark:bg-slate-900"
                 role="status" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3800)"
                 x-show="show" x-cloak x-transition.opacity.duration.300ms>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <header class="mb-6">
            <h1 class="mt-1 text-3xl font-bold leading-tight tracking-tight sm:text-center sm:text-4xl">SSC Attendance Claim</h1>
        </header>

        <section class="{{ $card }}">
            <div class="px-5 py-16 text-center text-slate-500 dark:text-slate-400">
                <div class="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-2xl bg-slate-100 text-indigo-500 dark:bg-slate-800">
                    <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                </div>
                <h2 class="mb-1 text-lg font-semibold text-slate-900 dark:text-slate-100">No events yet</h2>
                <p class="mx-auto mb-5 max-w-sm text-sm">Add an event first (for example “Intramurals – Day 1”). Each event has its own morning and afternoon login and logout.</p>
                <button type="button" class="{{ $btnPrimary }} inline-flex" @click="eventModal = true">
                    <svg class="h-[18px] w-[18px]" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4a1 1 0 0 1 1 1v4h4a1 1 0 1 1 0 2h-4v4a1 1 0 1 1-2 0v-4H5a1 1 0 1 1 0-2h4V5a1 1 0 0 1 1-1Z"/></svg>
                    Add event
                </button>
            </div>
        </section>

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
                           placeholder="e.g. Intramurals – Day 1" autocomplete="off" class="{{ $inpLg }}">
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
    </div>
</x-layout>
