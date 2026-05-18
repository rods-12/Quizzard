@php
    $user = auth()->user();
    $canViewPeopleAnalytics = $user && in_array($user->role, ['admin', 'superadmin'], true);

    $links = [
        ['route' => 'admin.analytics.overview', 'active' => 'admin.analytics.overview', 'label' => 'Overview'],
    ];

    if ($canViewPeopleAnalytics) {
        $links[] = ['route' => 'admin.analytics.teachers', 'active' => 'admin.analytics.teachers*', 'label' => 'Teachers'];
        $links[] = ['route' => 'admin.analytics.students', 'active' => 'admin.analytics.students*', 'label' => 'Students'];
        $links[] = ['route' => 'admin.analytics.classes', 'active' => 'admin.analytics.classes*', 'label' => 'Classes'];
        $links[] = ['route' => 'admin.analytics.quizzes', 'active' => 'admin.analytics.quizzes*', 'label' => 'Quizzes'];
    }
@endphp

<nav class="flex flex-wrap gap-2" aria-label="Analytics sections">
    @foreach($links as $link)
        <a href="{{ route($link['route'], $link['params'] ?? []) }}"
           class="rounded-xl px-5 py-2.5 text-sm font-semibold transition
                  {{ request()->routeIs($link['active']) ? 'bg-blue-700 text-white shadow-md' : 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
