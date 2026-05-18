@php
    $filters = $filters ?? [];
    $extraFields = $extraFields ?? '';
    $showSearch = $showSearch ?? true;
    $formAction = isset($actionUrl) ? $actionUrl : route($routeName);
    $resetAction = isset($resetUrl) ? $resetUrl : $formAction;
    $dateMode = old('date_mode', $filters['date_mode'] ?? 'all');
@endphp

<form method="GET" action="{{ $formAction }}" class="rounded-xl bg-white px-3 py-3 shadow-sm ring-1 ring-slate-200" data-analytics-filter novalidate>
    @if(isset($errors) && $errors->any())
        <div class="mb-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="flex flex-wrap items-end gap-2">
        @php
            $controlClass = 'rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100';
        @endphp

        <label class="flex flex-col gap-1">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Date Range</span>
            <select name="date_mode" class="{{ $controlClass }} min-w-[140px]" data-date-mode>
                <option value="all" {{ $dateMode === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="range" {{ $dateMode === 'range' ? 'selected' : '' }}>Custom Range</option>
            </select>
        </label>

        <label class="flex flex-col gap-1" data-date-field>
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">From</span>
            <input type="date" name="date_from" value="{{ old('date_from', $filters['date_from'] ?? '') }}"
                   class="{{ $controlClass }}" data-date-input>
        </label>

        <label class="flex flex-col gap-1" data-date-field>
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">To</span>
            <input type="date" name="date_to" value="{{ old('date_to', $filters['date_to'] ?? '') }}"
                   class="{{ $controlClass }}" data-date-input>
        </label>

        @if($showSearch)
            <label class="flex min-w-[190px] flex-col gap-1">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Search</span>
                <input type="search" name="search" value="{{ old('search', $filters['search'] ?? '') }}" maxlength="100"
                       placeholder="Search..."
                       class="{{ $controlClass }} w-full">
            </label>
        @endif

        {!! $extraFields !!}

        <div class="w-full text-sm font-medium text-red-600 hidden" data-filter-error></div>

        <button type="submit" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500" data-filter-apply>
            Apply
        </button>
        @if(request()->query())
            <a href="{{ $resetAction }}" title="Clear all active filters and return to All Time." class="rounded-lg border border-slate-300 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100">
                Clear
            </a>
        @endif
    </div>
</form>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-analytics-filter]').forEach((form) => {
                const mode = form.querySelector('[data-date-mode]');
                const fields = form.querySelectorAll('[data-date-field]');
                const error = form.querySelector('[data-filter-error]');
                const apply = form.querySelector('[data-filter-apply]');
                const dateInputs = form.querySelectorAll('[data-date-input]');
                const initialState = new FormData(form);
                const setError = (message) => {
                    if (!error) return;
                    error.textContent = message || '';
                    error.classList.toggle('hidden', !message);
                };
                const validFourDigitYear = (value) => {
                    if (!value) return false;
                    const year = value.split('-')[0] || '';
                    return /^\d{4}$/.test(year);
                };
                const validate = () => {
                    const currentState = new FormData(form);
                    let changed = false;
                    for (const [key, value] of currentState.entries()) {
                        if ((initialState.get(key) || '') !== value) {
                            changed = true;
                            break;
                        }
                    }
                    if (mode.value !== 'range') {
                        setError('');
                        if (apply) apply.disabled = !changed;
                        return true;
                    }
                    const from = form.querySelector('[name="date_from"]');
                    const to = form.querySelector('[name="date_to"]');
                    let message = '';
                    if (!from.value || !to.value) {
                        message = 'Choose both start and end dates, or use All Time.';
                    } else if (!validFourDigitYear(from.value) || !validFourDigitYear(to.value)) {
                        message = 'Years must be exactly 4 digits.';
                    } else if (to.value < from.value) {
                        message = 'End date must be the same as or after the start date.';
                    }
                    setError(message);
                    if (apply) apply.disabled = Boolean(message) || !changed;
                    return !message;
                };
                const syncDateFields = () => {
                    const custom = mode.value === 'range';
                    fields.forEach((field) => {
                        field.classList.toggle('hidden', !custom);
                        field.querySelectorAll('input').forEach((input) => input.disabled = !custom);
                    });
                    validate();
                };
                mode.addEventListener('change', syncDateFields);
                dateInputs.forEach((input) => input.addEventListener('input', validate));
                form.querySelectorAll('input, select').forEach((input) => input.addEventListener('change', validate));
                form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => input.addEventListener('input', validate));
                form.addEventListener('submit', (event) => {
                    if (!validate()) {
                        event.preventDefault();
                    }
                });
                syncDateFields();
            });
        </script>
    @endpush
@endonce
