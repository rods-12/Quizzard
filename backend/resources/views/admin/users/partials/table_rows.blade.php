{{-- admin/users/partials/table_rows.blade.php --}}
<div class="overflow-x-auto p-2">
    <table class="min-w-full table-fixed border-separate border-spacing-y-3 text-sm text-slate-700">
        <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
            <tr>
                <th class="w-16 px-4 py-3">ID</th>
                <th class="w-48 px-4 py-3">Name</th>
                <th class="w-56 px-4 py-3">Email</th>
                <th class="w-28 px-4 py-3">Role</th>
                <th class="w-32 px-4 py-3">Created</th>
                <th class="w-48 px-4 py-3">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="group">

                    {{-- ID --}}
                    <td class="rounded-l-2xl border-y border-l border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap font-semibold text-slate-800 group-hover:text-blue-700">#{{ $user->id }}</p>
                        </div>
                    </td>

                    {{-- Name --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap font-medium text-slate-800">{{ $user->name }}</p>
                        </div>
                    </td>

                    {{-- Email --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap text-slate-800">{{ $user->email }}</p>
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

                    {{-- Created --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap text-slate-700">{{ optional($user->created_at)->format('M d, Y') ?? '-' }}</p>
                        </div>
                    </td>

                    {{-- Actions --}}
                    <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="flex flex-wrap gap-2">
                            <button type="button"
                                    class="btn-view-user inline-flex items-center rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    data-id="{{ $user->id }}">
                                View
                            </button>
                            <button type="button"
                                    class="btn-edit-user inline-flex items-center rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-email="{{ $user->email }}"
                                    data-role="{{ $user->role }}"
                                    data-update-url="{{ route('admin.users.update', $user) }}">
                                Update
                            </button>
                            <button type="button"
                                    class="btn-delete-user inline-flex items-center rounded-xl bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    data-id="{{ $user->id }}"
                                    data-name="{{ $user->name }}"
                                    data-delete-url="{{ route('admin.users.destroy', $user) }}">
                                Delete
                            </button>
                        </div>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="6" class="rounded-2xl bg-white px-4 py-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
        {{ $users->links() }}
    </div>
@endif