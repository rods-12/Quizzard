@extends('admin.layouts.app')

@section('title', 'Classes')

@php $isSuperAdmin = auth()->check() && auth()->user()->role === 'superadmin'; @endphp

@section('content')
<div class="space-y-6">

@if($isSuperAdmin)
{{-- ================================================================ --}}
{{-- SUPERADMIN THEME                                                  --}}
{{-- ================================================================ --}}

    {{-- Hero --}}
    <div class="relative overflow-hidden rounded-2xl p-7 text-white"
         style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%); border: 1px solid rgba(99,102,241,0.3);">
        <div class="absolute -top-12 -right-12 h-40 w-40 rounded-full" style="background: radial-gradient(circle, rgba(99,102,241,0.2), transparent 70%);"></div>
        <div class="absolute -bottom-10 -left-10 h-32 w-32 rounded-full" style="background: radial-gradient(circle, rgba(139,92,246,0.15), transparent 70%);"></div>
        <div class="relative z-10">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color:#a5b4fc;">SuperAdmin Panel</p>
            <h1 class="mt-1.5 text-3xl font-bold text-white sm:text-4xl">Classes Management</h1>
            <p class="mt-2 max-w-2xl text-sm" style="color:#c7d2fe;">
                Create, view, update, and manage all classes across the platform.
            </p>
        </div>
    </div>

    {{-- Widgets --}}
    <div id="classWidgets" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-xl p-5 shadow-sm" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-sm font-medium" style="color:#475569;">Active Teachers</p>
            <h2 id="activeTeachersCount" class="mt-2 text-3xl font-bold text-white">{{ $activeTeachers }}</h2>
        </div>
        <div class="rounded-xl p-5 shadow-sm" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-sm font-medium" style="color:#475569;">Total Students</p>
            <h2 id="studentsCount" class="mt-2 text-3xl font-bold text-white">{{ $studentsCount }}</h2>
        </div>
        <div class="rounded-xl p-5 shadow-sm" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-sm font-medium" style="color:#475569;">Classes</p>
            <h2 id="classesCount" class="mt-2 text-3xl font-bold text-white">{{ $classesCount }}</h2>
        </div>
        <div class="rounded-xl p-5 shadow-sm" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
            <p class="text-sm font-medium" style="color:#475569;">Total Enrollments</p>
            <h2 class="mt-2 text-3xl font-bold text-white">{{ $totalEnrollments }}</h2>
        </div>
    </div>

    {{-- Controls --}}
    <div class="rounded-2xl p-6 shadow-lg" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto xl:flex-1">
                <div class="w-full xl:max-w-3xl">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by class title or teacher..."
                        class="w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                        style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#e2e8f0;"
                    >
                </div>
                <div class="w-full sm:w-48">
                    <select
                    id="sortFilter"
                    class="sa-select w-full rounded-lg border px-4 py-2.5 text-sm outline-none transition"
                >
                        <option value="latest">Latest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
            </div>
            <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                <button
                    type="button"
                    id="btnCreateClass"
                    class="inline-flex items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-60"
                    style="background:#6366f1;"
                    onmouseover="this.style.background='#4f46e5';"
                    onmouseout="this.style.background='#6366f1';">
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Create Class</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl overflow-hidden" style="background:#161b27;border:1px solid rgba(255,255,255,0.06);">
        <div class="overflow-x-auto">
            <div id="classesTableWrapper" style="background:#0f1117;">
                @include('admin.classes.partials.table', ['classes' => $classes])
            </div>
        </div>
    </div>

@else
{{-- ================================================================ --}}
{{-- ADMIN THEME — 100% original, zero changes                        --}}
{{-- ================================================================ --}}

    <!-- Hero -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-6 md:p-8 text-white shadow-lg">
        <div class="relative z-10">
            <h1 class="text-2xl md:text-3xl font-bold">Classes Management</h1>
            <p class="mt-2 text-sm md:text-base text-white/90">
                Create, view, update, and manage all classes across the platform.
            </p>
        </div>
        <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-10 -left-10 h-40 w-40 rounded-full bg-white/10"></div>
    </div>

    <!-- Widgets -->
    <div id="classWidgets" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Active Teachers</p>
            <h2 id="activeTeachersCount" class="mt-2 text-3xl font-bold text-slate-800">{{ $activeTeachers }}</h2>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Total Students</p>
            <h2 id="studentsCount" class="mt-2 text-3xl font-bold text-slate-800">{{ $studentsCount }}</h2>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Classes</p>
            <h2 id="classesCount" class="mt-2 text-3xl font-bold text-slate-800">{{ $classesCount }}</h2>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
            <p class="text-sm font-medium text-slate-500">Total Enrollments</p>
            <h2 class="mt-2 text-3xl font-bold text-slate-800">{{ $totalEnrollments }}</h2>
        </div>
    </div>

    <!-- Controls -->
    <div class="rounded-3xl bg-white p-6 shadow-lg ring-1 ring-slate-200">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto xl:flex-1">
                <div class="w-full xl:max-w-3xl">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by class title or teacher..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                </div>

                <div class="w-full sm:w-48">
                    <select
                        id="sortFilter"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm shadow-sm outline-none transition focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100"
                    >
                        <option value="latest">Latest</option>
                        <option value="oldest">Oldest</option>
                    </select>
                </div>
            </div>

            <div class="flex w-full flex-col gap-3 sm:flex-row xl:w-auto">
                <button
                    type="button"
                    id="btnCreateClass"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span class="flex items-center justify-center gap-2">
                        <span class="spinner hidden h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        <span>Create Class</span>
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="rounded-2xl bg-white shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <div id="classesTableWrapper">
                @include('admin.classes.partials.table', ['classes' => $classes])
            </div>
        </div>
    </div>

@endif

</div>

@include('admin.classes.modals.create')
@include('admin.classes.modals.view')
@include('admin.classes.modals.edit')
@include('admin.classes.modals.delete')

<div id="pageLoadingOverlay" class="fixed inset-0 z-[99999] hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div style="background:#0f172a; border:1px solid #334155; box-shadow:0 25px 50px rgba(0,0,0,.45);"
         class="flex min-w-[340px] flex-col items-center justify-center rounded-3xl px-10 py-10 text-white">
        <svg class="h-20 w-20 animate-spin" viewBox="0 0 50 50" aria-hidden="true">
            <circle cx="25" cy="25" r="20" fill="none" stroke="#334155" stroke-width="6"></circle>
            <path d="M25 5a20 20 0 0 1 20 20" fill="none" stroke="#60a5fa" stroke-width="6" stroke-linecap="round"></path>
        </svg>
        <div class="mt-6 text-xl font-bold tracking-[0.25em] text-white">
            LOADING
        </div>
        <div class="mt-3 text-sm text-slate-300">
            Opening class details...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const sortFilter = document.getElementById('sortFilter');
    const tableWrapper = document.getElementById('classesTableWrapper');

    const createModal = document.getElementById('createClassModal');
    const viewModal = document.getElementById('viewClassModal');
    const editModal = document.getElementById('editClassModal');
    const deleteModal = document.getElementById('deleteClassModal');

    const createForm = document.getElementById('createClassForm');
    const editForm = document.getElementById('editClassForm');
    const deleteForm = document.getElementById('deleteClassForm');
    const btnCreateClass = document.getElementById('btnCreateClass');

    let searchTimeout = null;
    let currentDeleteId = null;

    function getActionButtons() {
        return document.querySelectorAll('.class-action-btn, #btnCreateClass');
    }

    function setButtonLoading(button, loadingText = 'Processing...') {
        if (!button) return;

        button.disabled = true;
        button.dataset.originalHtml = button.innerHTML;

        const spinner = button.querySelector('.spinner');
        if (spinner) {
            spinner.classList.remove('hidden');
        }

        const textSpan = button.querySelector('span > span:last-child');
        if (textSpan) {
            textSpan.textContent = loadingText;
        }
    }

    function resetButtonLoading(button) {
        if (!button) return;

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }

        button.disabled = false;
    }

    function disableOtherActionButtons(activeButton = null) {
        getActionButtons().forEach(button => {
            if (button !== activeButton) {
                button.disabled = true;
            }
        });
    }

    function enableAllActionButtons() {
        getActionButtons().forEach(button => {
            button.disabled = false;
        });
    }

    function showPageLoadingOverlay() {
        const overlay = document.getElementById('pageLoadingOverlay');
        if (!overlay) return;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function hidePageLoadingOverlay() {
        const overlay = document.getElementById('pageLoadingOverlay');
        if (!overlay) return;
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        const hasVisibleModal = [createModal, viewModal, editModal, deleteModal].some(
            currentModal => currentModal && !currentModal.classList.contains('hidden')
        );

        if (!hasVisibleModal) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    if (btnCreateClass && createModal) {
        btnCreateClass.addEventListener('click', function () {
            disableOtherActionButtons(btnCreateClass);
            openModal(createModal);
        });
    }

    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', function () {
            closeModal(createModal);
            closeModal(viewModal);
            closeModal(editModal);
            closeModal(deleteModal);
            enableAllActionButtons();
        });
    });

    [createModal, viewModal, editModal, deleteModal].forEach(modal => {
        if (!modal) return;

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal(modal);
                enableAllActionButtons();
            }
        });
    });

    function fetchClasses(pageUrl = null) {
        const search = searchInput ? searchInput.value : '';
        const sort = sortFilter ? sortFilter.value : 'latest';

        const url = pageUrl ?? `{{ route('admin.classes') }}?search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}`;

        tableWrapper.innerHTML = `
            <div class="py-12 text-center">
                <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-slate-300 border-t-slate-600"></div>
                <p class="mt-3 text-sm text-slate-600">Loading classes...</p>
            </div>
        `;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            tableWrapper.innerHTML = html;
            bindTableActions();
            enableAllActionButtons();
        })
        .catch(() => {
            tableWrapper.innerHTML = `
                <div class="py-8 text-center text-rose-600 text-sm">
                    Failed to load classes.
                </div>
            `;
            enableAllActionButtons();
        });
    }

    function bindTableActions() {

        document.querySelectorAll('.class-row').forEach(row => {
            row.addEventListener('click', function (e) {
                const clickedActionButton = e.target.closest('.view-class-btn, .edit-class-btn, .delete-class-btn');
                if (clickedActionButton) {
                    return;
                }

                if (this.dataset.loading === 'true') return;

                this.dataset.loading = 'true';
                this.classList.add('opacity-70');

                document.querySelectorAll('.class-row').forEach(otherRow => {
                    if (otherRow !== this) {
                        otherRow.classList.add('pointer-events-none', 'opacity-60');
                    }
                });

                showPageLoadingOverlay();

                const url = this.dataset.url;

                setTimeout(() => {
                    window.location.href = url;
                }, 350);
            });
        });

        document.querySelectorAll('.view-class-btn').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;

                disableOtherActionButtons(button);
                setButtonLoading(button, button.dataset.loadingText || 'Loading...');

                fetch(`/admin/classes/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('viewClassName').textContent = data.name ?? '—';
                        document.getElementById('viewClassCode').textContent = data.class_code ?? '—';
                        document.getElementById('viewClassDescription').textContent = data.description ?? '—';
                        document.getElementById('viewClassTeacher').textContent = data.teacher?.name ?? '—';
                        document.getElementById('viewClassStudentCount').textContent = data.students?.length ?? 0;

                        resetButtonLoading(button);
                        openModal(viewModal);
                    })
                    .catch(() => {
                        resetButtonLoading(button);
                        enableAllActionButtons();
                        alert('Failed to load class details.');
                    });
            });
        });

        document.querySelectorAll('.edit-class-btn').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.dataset.id;

                disableOtherActionButtons(button);
                setButtonLoading(button, button.dataset.loadingText || 'Loading...');

                fetch(`/admin/classes/${id}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('editClassId').value = data.id;
                        document.getElementById('editClassName').value = data.name ?? '';
                        document.getElementById('editClassDescription').value = data.description ?? '';

                        resetButtonLoading(button);
                        openModal(editModal);
                    })
                    .catch(() => {
                        resetButtonLoading(button);
                        enableAllActionButtons();
                        alert('Failed to load class details.');
                    });
            });
        });

        document.querySelectorAll('.delete-class-btn').forEach(button => {
            button.addEventListener('click', function () {
                disableOtherActionButtons(button);
                setButtonLoading(button, button.dataset.loadingText || 'Loading...');

                currentDeleteId = this.dataset.id;
                document.getElementById('deleteClassName').textContent = this.dataset.name;

                resetButtonLoading(button);
                openModal(deleteModal);
            });
        });

        document.querySelectorAll('#classesTableWrapper .pagination a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                fetchClasses(this.href);
            });
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchClasses();
            }, 300);
        });
    }

    if (sortFilter) {
        sortFilter.addEventListener('change', function () {
            fetchClasses();
        });
    }

    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = document.getElementById('createClassSubmitBtn');
            disableOtherActionButtons(submitBtn);
            setButtonLoading(submitBtn, 'Creating...');

            const formData = new FormData(createForm);

            fetch(`{{ route('admin.classes.store') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to create class.');
                }

                closeModal(createModal);
                createForm.reset();
                fetchClasses();

                setTimeout(() => window.location.reload(), 250);
            })
            .catch(error => {
                alert(error.message || 'Failed to create class.');
            })
            .finally(() => {
                resetButtonLoading(submitBtn);
                enableAllActionButtons();
            });
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const submitBtn = editForm.querySelector('button[type="submit"]');
            const id = document.getElementById('editClassId').value;
            const formData = new FormData(editForm);

            disableOtherActionButtons(submitBtn);
            setButtonLoading(submitBtn, 'Saving...');

            fetch(`/admin/classes/${id}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-HTTP-Method-Override': 'PUT',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to update class.');
                }

                if (data.success) {
                    closeModal(editModal);
                    fetchClasses();
                    setTimeout(() => window.location.reload(), 250);
                }
            })
            .catch(error => {
                alert(error.message || 'Failed to update class.');
            })
            .finally(() => {
                resetButtonLoading(submitBtn);
                enableAllActionButtons();
            });
        });
    }

    if (deleteForm) {
        deleteForm.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!currentDeleteId) return;

            const submitBtn = deleteForm.querySelector('button[type="submit"]');
            disableOtherActionButtons(submitBtn);
            setButtonLoading(submitBtn, 'Deleting...');

            fetch(`/admin/classes/${currentDeleteId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Failed to delete class.');
                }

                if (data.success) {
                    closeModal(deleteModal);
                    fetchClasses();
                    setTimeout(() => window.location.reload(), 250);
                }
            })
            .catch(error => {
                alert(error.message || 'Failed to delete class.');
            })
            .finally(() => {
                resetButtonLoading(submitBtn);
                enableAllActionButtons();
            });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal(createModal);
            closeModal(viewModal);
            closeModal(editModal);
            closeModal(deleteModal);
            enableAllActionButtons();
        }
    });

    bindTableActions();
});
</script>
@endsection
