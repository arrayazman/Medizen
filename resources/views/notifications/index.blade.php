@extends('layouts.app')

@section('title', 'Notifikasi')
@section('page-title', 'Pusat Notifikasi')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm" style="border-radius: 8px;">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">Kotak Masuk Notifikasi</h6>
                    @if($notifications->count() > 0)
                        <form action="{{ route('notifications.mark-all-read') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-emerald btn-sm px-3" style="font-size: 0.75rem;">
                                Tandai Semua Dibaca
                            </button>
                        </form>
                    @endif
                </div>
                <div class="card-body p-0">
                    @forelse($notifications as $notification)
                        <div
                            class="p-3 border-bottom d-flex align-items-center gap-3 {{ $notification->read_at ? 'opacity-75' : 'bg-light-soft' }}">
                            <div class="rounded-circle bg-emerald-soft p-2 flex-shrink-0"
                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i data-feather="{{ $notification->read_at ? 'mail' : 'bell' }}"
                                    style="width: 18px; color: var(--primary-emerald)"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ $notification->data['url'] ?? '#' }}"
                                        onclick="markNotificationRead('{{ $notification->id }}', event, '{{ $notification->data['url'] ?? '#' }}')"
                                        class="text-decoration-none text-dark fw-bold small">
                                        {{ $notification->data['message'] }}
                                    </a>
                                    <span class="text-muted"
                                        style="font-size: 0.7rem;">{{ $notification->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="text-muted small">Pemeriksaan: {{ $notification->data['examination'] ?? '-' }} |
                                    Prioritas: {{ $notification->data['priority'] ?? 'ROUTINE' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i data-feather="bell-off" class="mb-3 text-muted"
                                style="width: 48px; height: 48px; opacity: 0.3;"></i>
                            <h6 class="text-muted">Tidak ada notifikasi untuk Anda</h6>
                        </div>
                    @endforelse
                </div>
                @if($notifications->hasPages())
                    <div class="card-footer bg-white py-2 d-flex justify-content-between align-items-center">
                        <small class="text-muted">{{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} dari {{ $notifications->total() }}</small>
                        <nav><ul class="pagination pagination-sm mb-0">
                            @if($notifications->onFirstPage())
                                <li class="page-item disabled"><span class="page-link">‹</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $notifications->previousPageUrl() }}">‹</a></li>
                            @endif
                            @foreach($notifications->getUrlRange(max(1,$notifications->currentPage()-2), min($notifications->lastPage(),$notifications->currentPage()+2)) as $page => $url)
                                @if($page == $notifications->currentPage())
                                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $notifications->url($page) }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                            @if($notifications->hasMorePages())
                                <li class="page-item"><a class="page-link" href="{{ $notifications->nextPageUrl() }}">›</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link">›</span></li>
                            @endif
                        </ul></nav>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .bg-light-soft {
            background-color: #f8fafc;
        }

        .bg-emerald-soft {
            background-color: rgba(16, 185, 129, 0.1);
        }

        :root {
            --primary-emerald: #10b981;
        }

        .btn-emerald {
            background-color: #10b981;
            color: white;
            border: none;
        }

        .btn-emerald:hover {
            background-color: #059669;
            color: white;
        }
    </style>
@endpush