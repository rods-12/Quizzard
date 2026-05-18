@php $isSuperAdmin = isset($isSuperAdmin) ? $isSuperAdmin : (auth()->check() && auth()->user()->role === 'superadmin'); @endphp

<div id="createClassModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

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
                            {{ $teacher->name }}{{ $teacher->email ? ' – ' . $teacher->email : '' }}
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
    maxlength="50"
    oninput="this.value.length >= 50 ? document.getElementById('createNameError').classList.remove('hidden') : document.getElementById('createNameError').classList.add('hidden')"
    required
>
<p id="createNameError" class="mt-1 hidden text-xs font-medium text-red-500">That's too long! Make it less than 50 characters.</p>
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
    maxlength="50"
    oninput="this.value.length >= 50 ? document.getElementById('createDescError').classList.remove('hidden') : document.getElementById('createDescError').classList.add('hidden')"
></textarea>
<p id="createDescError" class="mt-1 hidden text-xs font-medium text-red-500">That's too long! Make it less than 50 characters.</p>
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

</div>
