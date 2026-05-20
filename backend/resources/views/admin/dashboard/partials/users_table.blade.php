@php
    $isSuperAdmin = isset($isSuperAdmin)
        ? $isSuperAdmin
        : (auth()->check() && auth()->user()->role === 'superadmin');
@endphp
//users._table.blade.php//
<div class="overflow-x-auto p-2">
    <table class="min-w-full table-fixed border-separate border-spacing-y-3 text-sm text-slate-700">
        <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="w-16 px-4 py-3">ID</th>
                <th class="w-36 px-4 py-3">First Name</th>
                <th class="w-28 px-4 py-3">Middle Initial</th>
                <th class="w-36 px-4 py-3">Surname</th>
                <th class="w-56 px-4 py-3">Email</th>
                <th class="w-28 px-4 py-3">Role</th>
                <th class="w-28 px-4 py-3">Status</th>
                <th class="w-40 px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="group cursor-pointer"
                    data-view-url="{{ route('admin.dashboard', [
                        'type'      => $type,
                        'search'    => request('search'),
                        'filter_by' => request('filter_by', 'all'),
                        'status'    => request('status', 'all'),
                        'view_user' => $user->id,
                    ]) }}">

                    {{-- ID --}}
                    <td class="rounded-l-2xl border-y border-l border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap font-semibold text-slate-800 group-hover:text-blue-700">#{{ $user->id }}</p>
                        </div>
                    </td>

                    {{-- First Name --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="truncate font-medium text-slate-800" title="{{ $user->first_name }}">{{ \Illuminate\Support\Str::limit($user->first_name ?? '-', 30) }}</p>
                        </div>
                    </td>

                    {{-- Middle Initial --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap text-slate-700">{{ $user->middle_initial ?? '-' }}</p>
                        </div>
                    </td>

                    {{-- Surname --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="truncate text-slate-700" title="{{ $user->surname }}">{{ \Illuminate\Support\Str::limit($user->surname ?? '-', 30) }}</p>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="truncate text-slate-800" title="{{ $user->email }}">{{ \Illuminate\Support\Str::limit($user->email, 30) }}</p>
                            <p class="mt-1 whitespace-nowrap text-xs font-medium text-blue-600 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                Click to view full account details
                            </p>
                        </div>
                    </td>

                    {{-- Role --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        @php
                            $roleClasses = match($user->role) {
                                'admin'      => 'bg-purple-100 text-purple-700',
                                'teacher'    => 'bg-blue-100 text-blue-700',
                                'student'    => 'bg-slate-100 text-slate-700',
                                'superadmin' => 'bg-indigo-100 text-indigo-700',
                                default      => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $roleClasses }}">
                            {{ $user->role }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        @php
                            $statusClasses = match($user->status) {
                                'active'      => 'bg-emerald-100 text-emerald-700',
                                'deactivated' => 'bg-red-100 text-red-700',
                                default       => 'bg-amber-100 text-amber-700',
                            };
                        @endphp
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize {{ $statusClasses }}">
                            {{ $user->status }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        @php
                            $hasProtectedActivity = ($user->role === 'teacher' && (($user->quizzes_count ?? 0) > 0 || ($user->taught_classes_count ?? 0) > 0))
                                || ($user->role === 'student' && (($user->enrolled_classes_count ?? 0) > 0 || ($user->quiz_attempts_count ?? 0) > 0));
                            $toggleRoute = $user->status === 'active'
                                ? route('admin.activation.deactivate', $user)
                                : route('admin.activation.activate', $user);
                            $toggleLabel = $user->status === 'active' ? 'Deactivate' : 'Activate';
                            $toggleClass = $user->status === 'active'
                                ? 'bg-red-600 hover:bg-red-700'
                                : 'bg-emerald-600 hover:bg-emerald-700';
                        @endphp
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    class="btn-edit-user inline-flex h-9 w-28 items-center justify-center rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                    data-id="{{ $user->id }}"
                                    data-first-name="{{ $user->first_name ?? '' }}"
                                    data-middle-initial="{{ $user->middle_initial ?? '' }}"
                                    data-surname="{{ $user->surname ?? '' }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-status="{{ $user->status }}"
                                    data-update-url="{{ route('admin.users.update', $user) }}">
                                Update
                            </button>
                            @if($hasProtectedActivity)
                                <form method="POST" action="{{ $toggleRoute }}" class="inline-flex w-28">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="btn-toggle-status inline-flex h-9 w-28 items-center justify-center rounded-xl px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition {{ $toggleClass }}">
                                        {{ $toggleLabel }}
                                    </button>
                                </form>
                            @else
                                <button type="button"
                                        class="btn-delete-user inline-flex h-9 w-28 items-center justify-center rounded-xl bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                                        data-name="{{ $user->name }}"
                                        data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                    Delete
                                </button>
                            @endif
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

@if($users->hasPages())
    <div id="usersPagination" class="border-t border-slate-200 bg-slate-50 px-4 py-4">
        {{ $users->links() }}
    </div>
@endif
