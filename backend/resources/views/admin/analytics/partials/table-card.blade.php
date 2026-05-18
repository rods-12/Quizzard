@php
    $title = $title ?? 'Table';
    $description = $description ?? null;
@endphp

<section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
    <div class="border-b border-slate-100 px-6 py-4">
        <h2 class="text-lg font-bold text-slate-900">{{ $title }}</h2>
        @if($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    <div class="overflow-x-auto">
        {{ $slot ?? '' }}
    </div>
</section>
