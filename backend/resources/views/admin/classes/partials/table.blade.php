{{-- admin/classes/partials/table.blade.php --}}
<div class="bg-white">
    <div class="overflow-x-auto p-2">
        <table class="w-full table-fixed border-separate border-spacing-y-3 text-sm text-slate-700">
            <thead class="text-left text-xs font-bold uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="w-48 px-4 py-3">Class</th>
                    <th class="w-40 px-4 py-3">Teacher</th>
                    <th class="w-24 px-4 py-3">Students</th>
                    <th class="w-32 px-4 py-3">Code</th>
                    <th class="w-32 px-4 py-3">Created</th>
                    <th class="w-40 px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($classes as $class)
                    <tr class="class-row group cursor-pointer" data-url="{{ route('admin.classes.details', $class->id) }}">

                        {{-- Class Name + Description --}}
                        <td class="max-w-0 rounded-l-2xl border-y border-l border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <p class="truncate font-semibold text-slate-800 group-hover:text-blue-700" title="{{ $class->name }}">{{ \Illuminate\Support\Str::limit($class->name, 30) }}</p>
<p class="mt-0.5 truncate text-xs text-slate-500" title="{{ $class->description }}">{{ $class->description ? \Illuminate\Support\Str::limit($class->description, 30) : 'No description' }}</p>
                            <p class="mt-1 truncate text-xs font-medium text-blue-600 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
                                Click to view class details
                            </p>
                        </td>

                        {{-- Teacher --}}
                        <td class="max-w-0 border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <p class="truncate text-slate-700">{{ $class->teacher->name ?? 'Unknown Teacher' }}</p>
                        </td>

                        {{-- Students --}}
                        <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <div class="overflow-x-auto">
                                <p class="whitespace-nowrap text-slate-700">{{ $class->students->count() }}</p>
                            </div>
                        </td>

                        {{-- Code --}}
                        <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <div class="overflow-x-auto">
                                <p class="whitespace-nowrap text-slate-700">{{ $class->class_code }}</p>
                            </div>
                        </td>

                        {{-- Created --}}
                        <td class="border-y border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-y-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <div class="overflow-x-auto">
                                <p class="whitespace-nowrap text-slate-700">{{ $class->created_at ? $class->created_at->format('M d, Y') : '—' }}</p>
                            </div>
                        </td>

                        {{-- Actions --}}
                        <td class="rounded-r-2xl border-y border-r border-slate-200 bg-white px-4 py-4 shadow-sm transition-all duration-200 ease-out
                                   group-hover:scale-[1.01] group-hover:border-blue-500 group-hover:bg-blue-50
                                   group-hover:shadow-[0_0_0_3px_rgba(59,130,246,0.18),0_16px_30px_-12px_rgba(15,23,42,0.25)]">
                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        onclick="event.stopPropagation();"
                                        class="edit-class-btn class-action-btn inline-flex items-center justify-center rounded-xl bg-amber-500 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
                                        data-id="{{ $class->id }}"
                                        data-loading-text="Loading...">
                                    <span class="flex items-center justify-center gap-2">
                                        <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                        <span>Update</span>
                                    </span>
                                </button>
                                <button type="button"
                                        onclick="event.stopPropagation();"
                                        class="delete-class-btn class-action-btn inline-flex items-center justify-center rounded-xl bg-red-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                                        data-id="{{ $class->id }}"
                                        data-name="{{ $class->name }}"
                                        data-loading-text="Loading...">
                                    <span class="flex items-center justify-center gap-2">
                                        <span class="spinner hidden h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                        <span>Delete</span>
                                    </span>
                                </button>
                            </div>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="rounded-2xl bg-white px-4 py-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                            No classes found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($classes->hasPages())
        <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
            {{ $classes->links() }}
        </div>
    @endif
</div>
