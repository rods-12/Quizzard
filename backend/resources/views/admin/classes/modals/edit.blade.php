<div id="editClassModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">

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
    maxlength="50"
    oninput="this.value.length >= 50 ? document.getElementById('editNameError').classList.remove('hidden') : document.getElementById('editNameError').classList.add('hidden')"
    required
>
<p id="editNameError" class="mt-1 hidden text-xs font-medium text-red-500">That's too long! Make it less than 50 characters.</p>
                </div>

                <div>
                    <label for="editClassDescription" class="block text-sm font-semibold text-slate-700 mb-1">Description</label>
                    <textarea
    id="editClassDescription"
    name="description"
    rows="4"
    class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
    maxlength="50"
    oninput="this.value.length >= 50 ? document.getElementById('editDescError').classList.remove('hidden') : document.getElementById('editDescError').classList.add('hidden')"
></textarea>
<p id="editDescError" class="mt-1 hidden text-xs font-medium text-red-500">That's too long! Make it less than 50 characters.</p>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 px-6 py-4">
                <button type="button" class="close-modal rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Save Changes</span>
                    </span>
                </button>
            </div>
        </form>
    </div>

</div>
