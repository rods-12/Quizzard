@php
    $title = $title ?? 'No data available';
    $message = $message ?? 'Try changing the filters or choosing All Time.';
@endphp

<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
    <p class="text-sm font-semibold text-slate-700">{{ $title }}</p>
    <p class="mt-1 text-sm text-slate-500">{{ $message }}</p>
</div>
