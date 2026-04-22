@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')
    <div class="medizen-card-minimal mb-4">
        <div class="card-body p-0">
            <div class="p-3 d-flex justify-content-between align-items-center border-bottom bg-light-soft">
                <div>
                    <h6 class="mb-0 fw-bold text-slate-800" style="font-size: 14px; letter-spacing: 0.5px;">User Management
                        System</h6>
                    <div class="text-muted small mt-1" style="font-size: 10px; letter-spacing: 0.5px;">Identities &
                        Permissions Control</div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <div class="medizen-indicator active" style="font-size: 9px;">{{ $users->total() }} ACCOUNTS</div>
                        <div class="small text-muted text-uppercase" style="font-size: 8px;">TOTAL REGISTERED</div>
                    </div>
                    <a href="{{ route('users.create') }}" class="btn btn-emerald medizen-btn-minimal py-2">
                        <i data-feather="plus" class="me-1" style="width: 14px;"></i> ADD NEW USER
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-medizen mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-transform: none; font-size: 0.75rem;">#</th>
                            <th style="text-transform: none; font-size: 0.75rem;">Full Name</th>
                            <th style="text-transform: none; font-size: 0.75rem;">Email Address</th>
                            <th style="text-transform: none; font-size: 0.75rem;">Account Role</th>
                            <th class="text-center" style="text-transform: none; font-size: 0.75rem;">Status</th>
                            <th class="text-end" style="text-transform: none; font-size: 0.75rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $u)
                            <tr>
                                <td class="text-muted fw-bold">{{ str_pad($users->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>
                                    <div class="fw-bold text-slate-800">{{ $u->name }}</div>
                                </td>
                                <td>
                                    <div class="text-slate-600"><i data-feather="mail" class="me-1" style="width: 10px;"></i>
                                        {{ $u->email }}</div>
                                </td>
                                <td>
                                    <div class="text-slate-600 fw-bold" style="font-size: 11px;">{{ $u->role_label }}</div>
                                </td>
                                <td class="text-center">
                                    <div class="medizen-indicator {{ $u->is_active ? 'active' : '' }}"
                                        style="font-size: 11px; text-transform: none;">
                                        <i data-feather="{{ $u->is_active ? 'check-circle' : 'slash' }}" class="me-1"
                                            style="width: 12px;"></i>
                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('users.edit', $u) }}"
                                            class="btn btn-light medizen-btn-action-minimal border" title="Modify Details">
                                            <i data-feather="edit-3"></i>
                                        </a>
                                        @if($u->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $u) }}" method="POST"
                                                class="d-inline swal-confirm" data-swal-title="Delete User?"
                                                data-swal-text="Hapus user {{ $u->name }} secara permanen dari sistem?">
                                                @csrf @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-outline-danger medizen-btn-action-minimal border"
                                                    title="Delete Account">
                                                    <i data-feather="trash-2"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center opacity-50">
                                        <i data-feather="users" style="width: 48px; height: 48px;"
                                            class="mb-3 text-emerald"></i>
                                        <h6 class="fw-bold">BELUM ADA DATA USER</h6>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="px-4 py-3 bg-light-soft d-flex justify-content-between align-items-center border-top">
                    <div class="text-muted fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                        SHOWING {{ $users->firstItem() ?? 0 }}-{{ $users->lastItem() ?? 0 }} OF {{ $users->total() }} ACCOUNTS
                    </div>
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm mb-0 gap-1">
                            @if($users->onFirstPage())
                                <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">‹ PREV</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $users->previousPageUrl() }}">‹ PREV</a></li>
                            @endif
                            
                            @foreach($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)
                                @if($page == $users->currentPage())
                                    <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 0.6rem; font-weight: bold;">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 0.6rem;" href="{{ $users->url($page) }}">{{ $page }}</a></li>
                                @endif
                            @endforeach

                            @if($users->hasMorePages())
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 0.6rem;" href="{{ $users->nextPageUrl() }}">NEXT ›</a></li>
                            @else
                                <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 0.6rem;">NEXT ›</span></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
@endsection