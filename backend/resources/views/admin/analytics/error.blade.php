@extends('admin.layouts.app')

@section('title', 'Analytics Error')

@section('content')
<div class="space-y-6">
    @include('admin.analytics.partials.nav')

    <div class="rounded-3xl border border-red-200 bg-red-50 p-8 text-red-800">
        <p class="text-sm font-semibold uppercase tracking-wide">Analytics Error</p>
        <h1 class="mt-2 text-2xl font-bold">{{ $section }}</h1>
        <p class="mt-2 text-sm">{{ $message }}</p>
        <a href="{{ route('admin.analytics.overview') }}"
           class="mt-5 inline-flex rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-500">
            Back to Analytics
        </a>
    </div>
</div>
@endsection
