@php
    $items = $items ?? [];
@endphp

<div class="grid grid-cols-2 gap-4 md:grid-cols-4">
    @forelse($items as $item)
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $item['label'] }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $item['value'] }}</p>
            @if(!empty($item['hint']))
                <p class="mt-1 text-xs text-slate-500">{{ $item['hint'] }}</p>
            @endif
        </div>
    @empty
        @include('admin.analytics.partials.empty-state', ['message' => 'No summary data available.'])
    @endforelse
</div>
