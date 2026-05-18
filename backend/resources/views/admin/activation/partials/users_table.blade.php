{{-- admin/activation/partials/users_table.blade.php --}}
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
                <th class="w-36 px-4 py-3 text-center">Action</th>
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

                    {{-- First Name --}}
                    <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        <div class="overflow-x-auto">
                            <p class="whitespace-nowrap font-medium text-slate-800">{{ $user->first_name ?? '-' }}</p>
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
                            <p class="whitespace-nowrap text-slate-700">{{ $user->surname ?? '-' }}</p>
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

                    {{-- Action --}}
                    <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-4 py-4 text-center shadow-sm transition-all duration-200 ease-out
                               group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                               group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                        @if($user->status === 'active')
                            <form method="POST" action="{{ route('admin.activation.deactivate', $user) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="activation-btn inline-flex items-center rounded-xl bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-70">
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.activation.activate', $user) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="activation-btn inline-flex items-center rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-70">
                                    Activate
                                </button>
                            </form>
                        @endif
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="8" class="rounded-2xl bg-white px-4 py-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                        No accounts found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
    <div id="activationPagination" class="border-t border-slate-200 bg-slate-50 px-4 py-4">
        {{ $users->links() }}
    </div>
@endif