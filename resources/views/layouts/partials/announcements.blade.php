@php
    $audience = auth()->user()?->isAdmin() ? 'admin' : 'client';
    $activeAnnouncements = \App\Models\Announcement::active()->forAudience($audience)->latest()->get();
@endphp

@foreach ($activeAnnouncements as $announcement)
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <strong>{{ $announcement->title }}</strong>
        <div class="small">{{ $announcement->body }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Fechar') }}"></button>
    </div>
@endforeach
