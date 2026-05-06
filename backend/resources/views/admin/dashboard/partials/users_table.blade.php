@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

@if($isSuperAdmin)
{{-- ===== SUPERADMIN TABLE ===== --}}
<style>
    .sa-row { transition: background 0.15s ease; }
    .sa-row:hover { background: rgba(99,102,241,0.06) !important; }
    .sa-row:hover .sa-id { color: #a5b4fc !important; }
    .sa-row:hover .sa-hint { opacity: 1 !important; }
</style>
<div class="overflow-x-auto p-2">
    <table class="min-w-full border-separate border-spacing-y-2 text-sm">
        <thead class="text-left text-xs font-bold uppercase tracking-wide" style="color:#475569;">
            <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">First Name</th>
                <th class="px-4 py-3">Middle Initial</th>
                <th class="px-4 py-3">Surname</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="sa-row group cursor-pointer"
                    style="background:rgba(255,255,255,0.02);"
                    data-view-url="{{ route('admin.dashboard', ['type' => $type, 'search' => request('search'), 'filter_by' => request('filter_by', 'all'), 'status' => request('status', 'all'), 'view_user' => $user->id]) }}">

                    <td class="sa-id rounded-l-xl border-y border-l px-4 py-4 font-semibold transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#a5b4fc;">
                        #{{ $user->id }}
                    </td>
                    <td class="border-y px-4 py-4 font-medium transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#e2e8f0;">
                        {{ $user->first_name ?? '-' }}
                    </td>
                    <td class="border-y px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $user->middle_initial ?? '-' }}
                    </td>
                    <td class="border-y px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        {{ $user->surname ?? '-' }}
                    </td>
                    <td class="border-y px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);color:#94a3b8;">
                        <div class="flex flex-col">
                            <span>{{ $user->email }}</span>
                            <span class="sa-hint mt-1 text-xs font-medium transition-opacity duration-200"
                                  style="color:#6366f1;opacity:0;">
                                Click to view full account details
                            </span>
                        </div>
                    </td>
                    <td class="border-y px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);">
                        @php
                            $roleStyle = match($user->role) {
                                'admin'    => 'background:rgba(139,92,246,0.15);color:#c4b5fd;',
                                'teacher'  => 'background:rgba(99,102,241,0.15);color:#a5b4fc;',
                                'student'  => 'background:rgba(100,116,139,0.15);color:#94a3b8;',
                                default    => 'background:rgba(100,116,139,0.15);color:#94a3b8;',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                              style="{{ $roleStyle }}">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td class="border-y px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);">
                        @php
                            $statusStyle = match($user->status) {
                                'active'      => 'background:rgba(16,185,129,0.12);color:#34d399;',
                                'deactivated' => 'background:rgba(239,68,68,0.12);color:#f87171;',
                                default       => 'background:rgba(245,158,11,0.12);color:#fbbf24;',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                              style="{{ $statusStyle }}">
                            {{ $user->status }}
                        </span>
                    </td>
                    <td class="rounded-r-xl border-y border-r px-4 py-4 transition-all duration-200"
                        style="border-color:rgba(255,255,255,0.06);">
                        <div class="flex flex-wrap gap-2">
                            {{-- inline onclick removed — delegated listener on document in index.blade.php handles this --}}
                            <button type="button"
                                    class="btn-edit-user inline-flex items-center rounded-lg px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition"
                                    style="background:#6366f1;"
                                    onmouseover="this.style.background='#4f46e5';"
                                    onmouseout="this.style.background='#6366f1';"
                                    data-id="{{ $user->id }}"
                                    data-first-name="{{ $user->first_name }}"
                                    data-middle-initial="{{ $user->middle_initial }}"
                                    data-surname="{{ $user->surname }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-status="{{ $user->status }}"
                                    data-update-url="{{ route('admin.users.update', $user) }}">
                                Update
                            </button>
                            <button type="button"
                                    class="btn-delete-user inline-flex items-center rounded-lg px-3.5 py-2 text-xs font-semibold shadow-sm transition"
                                    style="background:rgba(239,68,68,0.2);color:#f87171;border:1px solid rgba(239,68,68,0.3);"
                                    onmouseover="this.style.background='rgba(239,68,68,0.35)';"
                                    onmouseout="this.style.background='rgba(239,68,68,0.2)';"
                                    data-name="{{ $user->name }}"
                                    data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="rounded-xl px-4 py-10 text-center text-sm"
                        style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);color:#475569;">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div id="usersPagination" class="px-4 py-4" style="border-top:1px solid rgba(255,255,255,0.06);">
        {{ $users->links() }}
    </div>
@endif

@else
{{-- ===== ADMIN TABLE — original design, only change: inline onclick removed --}}
<div class="overflow-x-auto p-2">
    <table class="min-w-full border-separate border-spacing-y-3 text-sm text-slate-700">
        <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">First Name</th>
                <th class="px-4 py-3">Middle Initial</th>
                <th class="px-4 py-3">Surname</th>
                <th class="px-4 py-3">Email</th>
                <th class="px-4 py-3">Role</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr
                    class="group cursor-pointer"
                    data-view-url="{{ route('admin.dashboard', ['type' => $type, 'search' => request('search'), 'filter_by' => request('filter_by', 'all'), 'status' => request('status', 'all'), 'view_user' => $user->id]) }}">

                    <td class="rounded-l-2xl border-y border-l border-slate-200 bg-white px-4 py-4 font-semibold text-slate-800 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:text-blue-700 group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:border-blue-500 group-hover:scale-[1.01]">
                        #{{ $user->id }}
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 font-medium text-slate-800 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        {{ $user->first_name ?? '-' }}
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        {{ $user->middle_initial ?? '-' }}
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        {{ $user->surname ?? '-' }}
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        <div class="flex flex-col">
                            <span class="text-slate-800">{{ $user->email }}</span>
                            <span class="mt-1 text-xs font-medium text-blue-600 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                Click to view full account details
                            </span>
                        </div>
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold capitalize text-blue-700">
                            {{ $user->role }}
                        </span>
                    </td>
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-y-blue-500 group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        @php
                            $statusClasses = match($user->status) {
                                'active' => 'bg-emerald-100 text-emerald-700',
                                'deactivated' => 'bg-red-100 text-red-700',
                                default => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $statusClasses }}">
                            {{ $user->status }}
                        </span>
                    </td>
                    <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out group-hover:bg-blue-50 group-hover:border-blue-500 group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)] group-hover:scale-[1.01]">
                        <div class="flex flex-wrap gap-2">
                            {{-- inline onclick removed — delegated listener on document in index.blade.php handles this --}}
                            <button type="button"
                                    class="btn-edit-user inline-flex items-center rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                    data-id="{{ $user->id }}"
                                    data-first-name="{{ $user->first_name }}"
                                    data-middle-initial="{{ $user->middle_initial }}"
                                    data-surname="{{ $user->surname }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-status="{{ $user->status }}"
                                    data-update-url="{{ route('admin.users.update', $user) }}">
                                Update
                            </button>
                            <button type="button"
                                    class="btn-delete-user inline-flex items-center rounded-xl bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                                    data-name="{{ $user->name }}"
                                    data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="rounded-2xl bg-white px-4 py-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($users->hasPages())
    <div id="usersPagination" class="border-t border-slate-200 bg-slate-50 px-4 py-4">
        {{ $users->links() }}
    </div>
@endif
@endif
