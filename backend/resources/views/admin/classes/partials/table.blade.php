@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

@if($isSuperAdmin)
{{-- ===== SUPERADMIN TABLE ===== --}}
<style>
    .sa-class-row { transition: background 0.15s ease; }
    .sa-class-row:hover { background: rgba(99,102,241,0.06) !important; }
    .sa-class-row:hover .sa-class-id { color: #a5b4fc !important; }
    .sa-class-row:hover .sa-class-title { color: #a5b4fc !important; }
    .sa-class-row:hover .sa-class-desc { color: #64748b !important; }
    .sa-class-row:hover .sa-class-hint { opacity: 1 !important; }
</style>

<div class="overflow-x-auto p-2">
    <table class="min-w-full border-separate border-spacing-y-2 text-sm">
        <thead class="text-left text-xs font-bold uppercase tracking-wide" style="color:#475569;">
            <tr>
                <th class="px-4 py-3">Class</th>
                <th class="px-4 py-3">Teacher</th>
                <th class="px-4 py-3">Students</th>
                <th class="px-4 py-3">Code</th>
                <th class="px-4 py-3">Created</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $class)
                <tr class="sa-class-row class-row group cursor-pointer"
                    style="background:rgba(255,255,255,0.02);"
                    data-url="{{ route('admin.classes.details', $class->id) }}">

                    <td class="rounded-l-xl border-y border-l px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);">
                        <div class="flex flex-col">
                            <span class="sa-class-title font-semibold transition" style="color:#e2e8f0;">
                                {{ $class->name }}
                            </span>
                            <span class="sa-class-desc mt-0.5 max-w-xs truncate text-xs transition" style="color:#475569;">
                                {{ $class->description ?: 'No description' }}
                            </span>
                            <span class="sa-class-hint mt-1 text-xs font-medium transition-opacity duration-200"
                                  style="color:#6366f1;opacity:0;">
                                Click to view class quizzes
                            </span>
                        </div>
                    </td>

                    <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $class->teacher->name ?? 'Unknown Teacher' }}
                    </td>

                    <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $class->students->count() }}
                    </td>

                    <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $class->class_code }}
                    </td>

                    <td class="border-y px-4 py-4 text-sm transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $class->created_at ? $class->created_at->format('M d, Y') : '—' }}
                    </td>

                    <td class="rounded-r-xl border-y border-r px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);">
                        <div class="flex items-center justify-center gap-2 flex-wrap">
                            <button type="button"
                                    onclick="event.stopPropagation();"
                                    class="edit-class-btn class-action-btn inline-flex items-center justify-center rounded-lg px-3.5 py-2 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-60"
                                    style="background:rgba(99,102,241,0.15);color:#a5b4fc;border:1px solid rgba(99,102,241,0.25);"
                                    onmouseover="this.style.background='rgba(99,102,241,0.28)';"
                                    onmouseout="this.style.background='rgba(99,102,241,0.15)';"
                                    data-id="{{ $class->id }}"
                                    data-loading-text="Loading...">
                                <span class="flex items-center justify-center gap-2">
                                    <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    <span>Update</span>
                                </span>
                            </button>
                            <button type="button"
                                    onclick="event.stopPropagation();"
                                    class="delete-class-btn class-action-btn inline-flex items-center justify-center rounded-lg px-3.5 py-2 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-60"
                                    style="background:rgba(239,68,68,0.12);color:#f87171;border:1px solid rgba(239,68,68,0.2);"
                                    onmouseover="this.style.background='rgba(239,68,68,0.25)';"
                                    onmouseout="this.style.background='rgba(239,68,68,0.12)';"
                                    data-id="{{ $class->id }}"
                                    data-name="{{ $class->name }}"
                                    data-loading-text="Loading...">
                                <span class="flex items-center justify-center gap-2">
                                    <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                    <span>Delete</span>
                                </span>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="rounded-xl px-4 py-10 text-center text-sm"
                        style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);color:#475569;">
                        No classes found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($classes->hasPages())
    <div class="px-4 py-4" style="border-top:1px solid rgba(255,255,255,0.06);">
        {{ $classes->links() }}
    </div>
@endif

@else
{{-- ===== ADMIN TABLE — 100% original, zero changes ===== --}}
<style>
    .class-row-hoverable {
        cursor: pointer;
        transition: background-color 0.18s ease, box-shadow 0.18s ease;
    }
    .class-row-hoverable:hover > td { background-color: #dbeafe !important; }
    .class-row-hoverable:hover .class-main-cell { background-color: #c7d2fe !important; border-color: #818cf8 !important; }
    .class-row-hoverable:hover .class-main-title { color: #312e81 !important; }
    .class-row-hoverable:hover .class-main-desc,
    .class-row-hoverable:hover .class-meta-text { color: #334155 !important; }
</style>

<table class="min-w-full divide-y divide-slate-200">
    <thead class="bg-slate-50">
        <tr>
            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Class</th>
            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Teacher</th>
            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Students</th>
            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Code</th>
            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Created</th>
            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 bg-white">
        @forelse($classes as $class)
            <tr class="class-row class-row-hoverable group" data-url="{{ route('admin.classes.details', $class->id) }}">
                <td class="px-6 py-4">
                    <div class="class-main-cell min-w-0 rounded-2xl border border-transparent p-3 transition duration-200">
                        <div class="class-main-title font-semibold text-slate-800">{{ $class->name }}</div>
                        <div class="class-main-desc mt-1 max-w-xs truncate text-sm text-slate-500">{{ $class->description ?: 'No description' }}</div>
                    </div>
                </td>
                <td class="class-meta-text px-6 py-4 text-sm text-slate-700">{{ $class->teacher->name ?? 'Unknown Teacher' }}</td>
                <td class="class-meta-text px-6 py-4 text-sm text-slate-700">{{ $class->students->count() }}</td>
                <td class="class-meta-text px-6 py-4 text-sm text-slate-700">{{ $class->class_code }}</td>
                <td class="class-meta-text px-6 py-4 text-sm text-slate-700">{{ $class->created_at ? $class->created_at->format('M d, Y') : '—' }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2 flex-wrap">
                        <button type="button"
                                class="edit-class-btn class-action-btn inline-flex items-center justify-center rounded-xl bg-amber-100 px-4 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-200 disabled:cursor-not-allowed disabled:opacity-60"
                                data-id="{{ $class->id }}" data-loading-text="Loading...">
                            <span class="flex items-center justify-center gap-2">
                                <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                <span>Update</span>
                            </span>
                        </button>
                        <button type="button"
                                class="delete-class-btn class-action-btn inline-flex items-center justify-center rounded-xl bg-rose-100 px-4 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-200 disabled:cursor-not-allowed disabled:opacity-60"
                                data-id="{{ $class->id }}" data-name="{{ $class->name }}" data-loading-text="Loading...">
                            <span class="flex items-center justify-center gap-2">
                                <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                                <span>Delete</span>
                            </span>
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500">No classes found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

@if($classes->hasPages())
    <div class="border-t border-slate-200 bg-white px-6 py-4">{{ $classes->links() }}</div>
@endif
@endif
