@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

<div id="editClassModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

    @if($isSuperAdmin)
    {{-- ===== SUPERADMIN EDIT CLASS MODAL ===== --}}
    <div class="w-full max-w-2xl rounded-2xl shadow-2xl"
         style="background:#161b27;border:1px solid rgba(255,255,255,0.08);">

        <div class="flex items-center justify-between px-6 py-4"
             style="border-bottom:1px solid rgba(255,255,255,0.06);">
            <h2 class="text-lg font-bold" style="color:#e2e8f0;">Update Class</h2>
            <button class="close-modal flex h-9 w-9 items-center justify-center rounded-full text-lg transition"
                    style="background:rgba(255,255,255,0.06);color:#94a3b8;"
                    onmouseover="this.style.background='rgba(255,255,255,0.12)';"
                    onmouseout="this.style.background='rgba(255,255,255,0.06)';">
                &times;
            </button>
        </div>

        <form id="editClassForm">
            @csrf
            <input type="hidden" id="editClassId" name="id">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="editClassName" class="mb-1.5 block text-sm font-semibold" style="color:#94a3b8;">
                        Class Title
                    </label>
                    <input type="text" id="editClassName" name="name"
                           class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                           style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;"
                           required>
                </div>

                <div>
                    <label for="editClassDescription" class="mb-1.5 block text-sm font-semibold" style="color:#94a3b8;">
                        Description
                    </label>
                    <textarea id="editClassDescription" name="description" rows="4"
                              class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                              style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;"></textarea>
                </div>
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
                        style="background:#6366f1;"
                        onmouseover="if(!this.disabled){this.style.background='#4f46e5';}"
                        onmouseout="if(!this.disabled){this.style.background='#6366f1';}">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Save Changes</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    @else
    {{-- ===== ADMIN EDIT CLASS MODAL — original, zero changes ===== --}}
    <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 class="text-lg font-bold text-slate-800">Update Class</h2>
            <button class="close-modal text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
        </div>

        <form id="editClassForm">
            @csrf
            <input type="hidden" id="editClassId" name="id">

            <div class="space-y-4 px-6 py-5">
                <div>
                    <label for="editClassName" class="block text-sm font-semibold text-slate-700 mb-1">Class Title</label>
                    <input
                        type="text"
                        id="editClassName"
                        name="name"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    >
                </div>

                <div>
                    <label for="editClassDescription" class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea
                        id="editClassDescription"
                        name="description"
                        rows="4"
                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                <button type="button" class="close-modal rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
