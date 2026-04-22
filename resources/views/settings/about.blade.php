@extends('layouts.app')

@section('content')
    <div id="about-page-runtime-container" class="container py-4">
        <div class="text-center py-5">
            <div class="spinner-border text-dark mb-2"></div>
            <p class="text-muted small">Initializing Runtime Components...</p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Placeholder for any page-specific minimal logic if needed.
        // The core UI is now handled by engine.bootstrap.js via #about-page-runtime-container
    </script>
@endpush

<style>
    body {
        background-color: #f4f7f6;
    }
</style>
