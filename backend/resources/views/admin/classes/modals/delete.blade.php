@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

<div id="deleteClassModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

    @if($isSuperAdmin)
    {{-- ===== SUPERADMIN DELETE CLASS MODAL ===== --}}
    <div class="w-full max-w-lg rounded-2xl shadow-2xl"
         style="background:#161b27;border:1px solid rgba(239,68,68,0.2);">

        <div class="px-6 py-4" style="border-bottom:1px solid rgba(255,255,255,0.06);">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold"
                     style="background:rgba(239,68,68,0.15);color:#f87171;">!</div>
                <h2 class="text-lg font-bold" style="color:#f87171;">Delete Class</h2>
            </div>
        </div>

        <form id="deleteClassForm">
            @csrf
            <div class="px-6 py-5">
                <p class="text-sm" style="color:#94a3b8;">
                    Are you sure you want to delete
                    <strong id="deleteClassName" style="color:#e2e8f0;"></strong>?
                </p>
                <p class="mt-2 text-sm" style="color:#f87171;">
                    This action cannot be undone and will also remove related student enrollments and assigned quiz links.
                </p>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4"
                 style="border-top:1px solid rgba(255,255,255,0.06);">
                <button type="button"
                        class="close-modal rounded-lg px-5 py-2.5 text-sm font-semibold transition"
                        style="background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)';"
                        onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    Cancel
                </button>
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                        style="background:rgba(220,38,38,0.85);"
                        onmouseover="if(!this.disabled){this.style.background='rgba(220,38,38,1)';}"
                        onmouseout="if(!this.disabled){this.style.background='rgba(220,38,38,0.85)';}">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Delete</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    @else
    {{-- ===== ADMIN DELETE CLASS MODAL — original, zero changes ===== --}}
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-bold text-rose-600">Delete Class</h2>
        </div>

        <form id="deleteClassForm">
            @csrf
            <div class="px-6 py-5">
                <p class="text-sm text-slate-700">
                    Are you sure you want to delete
                    <span id="deleteClassName" class="font-bold text-slate-900"></span>?
                </p>
                <p class="mt-2 text-sm text-rose-500">
                    This action cannot be undone and will also remove related student enrollments and assigned quiz links.
                </p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                <button type="button" class="close-modal rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">
                    Delete
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
