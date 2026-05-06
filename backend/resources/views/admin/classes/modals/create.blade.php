@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

<div id="createClassModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

    @if($isSuperAdmin)
    {{-- ===== SUPERADMIN CREATE CLASS MODAL ===== --}}
    <div class="relative w-full max-w-2xl rounded-2xl p-6 shadow-2xl"
         style="background:#161b27;border:1px solid rgba(255,255,255,0.08);">
        <button type="button"
                class="close-modal absolute right-4 top-4 flex h-9 w-9 items-center justify-center rounded-full text-lg transition"
                style="background:rgba(255,255,255,0.06);color:#94a3b8;"
                onmouseover="this.style.background='rgba(255,255,255,0.12)';"
                onmouseout="this.style.background='rgba(255,255,255,0.06)';">
            &times;
        </button>

        <div class="mb-5">
            <h3 class="text-xl font-bold" style="color:#e2e8f0;">Create Class</h3>
            <p class="mt-1 text-sm" style="color:#475569;">
                Fill in the class details below. Class code will be generated automatically.
            </p>
        </div>

        <form id="createClassForm" class="space-y-4">
            @csrf

            <div>
                <label for="createClassTeacher" class="mb-1.5 block text-sm font-medium" style="color:#94a3b8;">
                    Teacher
                </label>
                <select id="createClassTeacher" name="teacher_id"
                        class="sa-select w-full rounded-lg border px-4 py-2.5 text-sm outline-none"
                        required>
                    <option value="">Select a teacher</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">
                            {{ $teacher->name }}{{ $teacher->email ? ' — ' . $teacher->email : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="createClassName" class="mb-1.5 block text-sm font-medium" style="color:#94a3b8;">
                    Class Title
                </label>
                <input type="text" id="createClassName" name="name"
                       class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                       style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;"
                       placeholder="Enter class title" required>
            </div>

            <div>
                <label for="createClassDescription" class="mb-1.5 block text-sm font-medium" style="color:#94a3b8;">
                    Description
                </label>
                <textarea id="createClassDescription" name="description" rows="4"
                          class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                          style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;"
                          placeholder="Enter class description (optional)"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button"
                        class="close-modal rounded-lg px-5 py-2.5 text-sm font-semibold transition"
                        style="background:rgba(255,255,255,0.05);color:#94a3b8;border:1px solid rgba(255,255,255,0.08);"
                        onmouseover="this.style.background='rgba(255,255,255,0.1)';"
                        onmouseout="this.style.background='rgba(255,255,255,0.05)';">
                    Cancel
                </button>
                <button id="createClassSubmitBtn" type="submit"
                        class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                        style="background:#6366f1;"
                        onmouseover="if(!this.disabled){this.style.background='#4f46e5';}"
                        onmouseout="if(!this.disabled){this.style.background='#6366f1';}">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Create Class</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

    @else
    {{-- ===== ADMIN CREATE CLASS MODAL — original, zero changes ===== --}}
    <div class="relative w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl">
        <button
            type="button"
            class="close-modal absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500 hover:bg-slate-200 hover:text-slate-700"
        >
            &times;
        </button>

        <div class="mb-6">
            <h3 class="text-2xl font-bold text-slate-900">Create Class</h3>
            <p class="mt-1 text-sm text-slate-500">
                Fill in the class details below. Class code will be generated automatically.
            </p>
        </div>

        <form id="createClassForm" class="space-y-4">
            @csrf

            <div>
                <label for="createClassTeacher" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Teacher
                </label>
                <select
                    id="createClassTeacher"
                    name="teacher_id"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    required
                >
                    <option value="">Select a teacher</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">
                            {{ $teacher->name }}{{ $teacher->email ? ' — ' . $teacher->email : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="createClassName" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Class Title
                </label>
                <input
                    type="text"
                    id="createClassName"
                    name="name"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    placeholder="Enter class title"
                    required
                >
            </div>

            <div>
                <label for="createClassDescription" class="mb-1.5 block text-sm font-medium text-slate-700">
                    Description
                </label>
                <textarea
                    id="createClassDescription"
                    name="description"
                    rows="4"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                    placeholder="Enter class description (optional)"
                ></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button
                    type="button"
                    class="close-modal rounded-xl bg-slate-100 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-200"
                >
                    Cancel
                </button>

                <button
                    id="createClassSubmitBtn"
                    type="submit"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Create Class</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
    @endif

</div>
