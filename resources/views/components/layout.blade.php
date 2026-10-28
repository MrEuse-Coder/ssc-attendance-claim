<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title>Attendance Claim</title>

    <style>[x-cloak] { display: none !important; }</style>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>

    <script>
        document.addEventListener('alpine:init', () => {

            // ---- New-claim form: dependent program combobox + live "already claimed" check ----
            Alpine.data('claimForm', (init) => ({
                eventId: init.eventId,
                college: init.college,
                program: init.program,
                year: init.year,
                section: init.section,
                claimSlot: init.slot,
                programs: init.programs,
                url: init.url,
                claimed: null,
                serverMsg: init.serverMsg,
                timer: null,
                checkCtrl: null,
                open: false,
                active: -1,
                filtering: false,
                busy: false,
                errors: {},
                formError: '',
                success: '',
                successTimer: null,

                init() {
                    ['college', 'program', 'year', 'section', 'claimSlot'].forEach(k =>
                        this.$watch(k, () => { this.serverMsg = false; this.errors = {}; this.queueCheck(); })
                    );
                    this.queueCheck();
                },

                // Submit with fetch so the form stays open: keep the slot, clear everything else.
                async submit(ev) {
                    const form = ev.target;
                    this.busy = true; this.errors = {}; this.formError = ''; this.serverMsg = false; this.success = '';
                    try {
                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(form),
                        });
                        const d = await res.json().catch(() => ({}));

                        if (res.status === 422) { this.errors = d.errors || {}; return; }
                        if (!res.ok) {
                            this.formError = res.status === 419
                                ? 'Your session expired. Refresh the page and try again.'
                                : 'Something went wrong. Please try again.';
                            return;
                        }

                        ['first_name', 'last_name'].forEach(id => {
                            const el = form.querySelector('#' + id);
                            if (el) el.value = '';
                        });
                        this.college = ''; this.program = ''; this.year = ''; this.section = '';
                        this.claimed = null;      // claimSlot is kept on purpose

                        this.$dispatch('claim-saved', d);   // the page refreshes the list + counts
                        this.flashSuccess(d.message);

                        // Phones: do NOT focus a field (the keyboard would pop up and hide the
                        // confirmation) and close any open keyboard. Desktop: jump to First name.
                        if (window.matchMedia('(pointer: fine)').matches) {
                            this.$nextTick(() => { const f = form.querySelector('#first_name'); if (f) f.focus(); });
                        } else if (document.activeElement && document.activeElement.blur) {
                            document.activeElement.blur();
                        }
                    } catch (e) {
                        this.formError = 'Could not reach the server. Check your connection and try again.';
                    } finally {
                        this.busy = false;
                    }
                },

                // Green confirmation shown inside the form, right above the buttons.
                flashSuccess(msg) {
                    this.success = msg || 'Claim saved.';
                    clearTimeout(this.successTimer);
                    this.successTimer = setTimeout(() => { this.success = ''; }, 4500);
                    this.$nextTick(() => {
                        const el = this.$refs.successBox;
                        if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    });
                },

                queueCheck() {
                    clearTimeout(this.timer);
                    this.timer = setTimeout(() => this.check(), 300);
                },

                async check() {
                    const program = this.program.trim();
                    if (!this.college || program.length < 2 || !this.year || !this.section || !this.claimSlot) {
                        this.claimed = null;
                        return;
                    }
                    if (this.checkCtrl) this.checkCtrl.abort();
                    this.checkCtrl = new AbortController();
                    const qs = new URLSearchParams({
                        event: this.eventId,
                        college: this.college, program: program, year: this.year, section: this.section, slot: this.claimSlot,
                    });
                    try {
                        const res = await fetch(this.url + '?' + qs.toString(), {
                            headers: { 'Accept': 'application/json' }, signal: this.checkCtrl.signal,
                        });
                        if (!res.ok) throw new Error('check failed');
                        const d = await res.json();
                        this.claimed = d.claimed ? d : null;
                    } catch (e) {
                        if (e.name !== 'AbortError') this.claimed = null;
                    }
                },

                // Case-insensitive lookup so a college typed as "ccs" still finds the "CCS" programs.
                get list() {
                    const c = this.college.trim().toLowerCase();
                    const key = Object.keys(this.programs).find(k => k.toLowerCase() === c);
                    return key ? this.programs[key] : [];
                },

                get options() {
                    const t = this.program.trim().toLowerCase();
                    return (this.filtering && t) ? this.list.filter(p => p.toLowerCase().includes(t)) : this.list;
                },

                get isCustom() {
                    const t = this.program.trim().toLowerCase();
                    return t !== '' && !this.list.some(p => p.toLowerCase() === t);
                },

                same(p) { return p.toLowerCase() === this.program.trim().toLowerCase(); },

                onCollege() {
                    // keep the program only if it belongs to the (new) college's listed programs
                    if (!this.list.some(p => p.toLowerCase() === this.program.trim().toLowerCase())) {
                        this.program = '';
                    }
                    this.open = false; this.active = -1; this.filtering = false;
                },

                openList() {
                    if (!this.college || this.open) return;
                    this.open = true; this.filtering = false; this.active = -1;
                },

                toggle() {
                    if (!this.college) return;
                    this.open = !this.open; this.filtering = false; this.active = -1;
                    if (this.open) this.$refs.prog.focus();
                },

                typing() { this.filtering = true; this.open = !!this.college; this.active = -1; },

                pick(p) { this.program = p; this.open = false; this.filtering = false; this.active = -1; },

                choose() { const p = this.options[this.active]; if (p) this.pick(p); },

                move(d) {
                    if (!this.college) return;
                    if (!this.open) { this.open = true; this.filtering = false; return; }
                    const n = this.options.length;
                    if (!n) return;
                    this.active = this.active < 0 ? (d > 0 ? 0 : n - 1) : (this.active + d + n) % n;
                    this.$nextTick(() => {
                        const el = this.$refs.list.querySelectorAll('[data-opt]')[this.active];
                        if (el) el.scrollIntoView({ block: 'nearest' });
                    });
                },
            }));

            // ---- Page: live search, filters, slot chips, modals ----
            Alpine.data('claims', (init) => ({
                modal: init.hasErrors,
                eventModal: init.eventModal,
                eventId: init.eventId,
                counts: init.counts,
                total: init.total,
                toast: { show: false, msg: '' },
                toastTimer: null,
                q: init.q,
                slot: init.slot,
                f: { ...init.filters },
                programMap: init.programs,
                showFilters: Object.values(init.filters).some(Boolean),
                loading: false,
                submitting: false,
                deleteUrl: null,
                deleteName: '',
                ctrl: null,

                askDelete(url, name) { this.deleteUrl = url; this.deleteName = name; },

                notify(msg) {
                    this.toast.msg = msg;
                    this.toast.show = true;
                    clearTimeout(this.toastTimer);
                    this.toastTimer = setTimeout(() => { this.toast.show = false; }, 3800);
                },

                // A claim was saved from the (still open) form.
                saved(d) {
                    this.counts = d.counts || {};
                    if (typeof d.total === 'number') this.total = d.total;
                    this.load(location.href);
                },

                async load(url) {
                    if (this.ctrl) this.ctrl.abort();
                    this.ctrl = new AbortController();
                    this.loading = true;
                    try {
                        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: this.ctrl.signal });
                        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                        const next = doc.querySelector('[data-results]');
                        if (!next) throw new Error('bad response');
                        this.$refs.results.innerHTML = next.innerHTML;
                        history.replaceState(null, '', url);
                    } catch (e) {
                        if (e.name !== 'AbortError') window.location = url; // graceful fallback
                    } finally {
                        this.loading = false;
                    }
                },

                search() {
                    const url = new URL(this.$refs.searchForm.action, location.href);
                    url.searchParams.set('event', this.eventId); // stay inside the current event
                    if (this.q.trim()) url.searchParams.set('q', this.q.trim());
                    if (this.slot) url.searchParams.set('slot', this.slot);
                    ['college', 'program', 'year', 'section'].forEach(k => {
                        if (k === 'program' && !this.f.college) return; // program only applies inside a college
                        if (this.f[k]) url.searchParams.set(k, this.f[k]);
                    });
                    this.load(url.toString());
                },

                get programChoices() { return this.programMap[this.f.college] || []; },

                get activeFilters() { return Object.values(this.f).filter(Boolean).length; },

                onFilterCollege() { this.f.program = ''; this.search(); },

                clearFilters() { this.f = { college: '', program: '', year: '', section: '' }; this.search(); },

                setSlot(v) { this.slot = v; this.search(); },

                clear() { this.q = ''; this.search(); this.$refs.q.focus(); },

                page(e) {
                    const a = e.target.closest('a[data-page]');
                    if (!a) return;
                    e.preventDefault();
                    this.load(a.href);
                },
            }));
        });
    </script>
</head>

<body class="min-h-screen bg-slate-50 font-sans leading-normal text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">

{{$slot}}
</body>
</html>
