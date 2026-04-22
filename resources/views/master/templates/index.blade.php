@extends('layouts.app')
@section('title', 'Template Expertise')
@section('page-title', 'Pustaka Laporan (Template)')

@section('content')
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div class="text-uppercase fw-bold text-slate-700" style="font-size: 13px; letter-spacing: 1px;">
            REPORT TEMPLATE DIRECTORY
        </div>
        <button type="button" class="btn btn-emerald medizen-btn-minimal" onclick="openAddModal()">
            <i data-feather="plus" class="me-1" style="width: 14px;"></i> TAMBAH TEMPLATE
        </button>
    </div>

    <div class="medizen-card-minimal">
        <!-- Search Area -->
        <div class="p-2 border-bottom">
            <form action="{{ route('master.templates.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="position-relative">
                        <i data-feather="search" class="position-absolute top-50 translate-middle-y ms-3 text-muted"
                            style="width: 12px;"></i>
                        <input type="text" name="search" class="form-control medizen-input-minimal ps-5"
                            placeholder="Cari berdasarkan nama atau kode..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-dark medizen-btn-minimal w-100">FILTER</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover medizen-table-minimal mb-0">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">KODE</th>
                        <th width="30%">NAMA PEMERIKSAAN</th>
                        <th width="35%">PREVIEW KONTEN</th>
                        <th width="15%" class="text-end">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $index => $t)
                        <tr>
                            <td class="text-muted fw-bold">
                                {{ str_pad($templates->firstItem() + $index, 2, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800">{{ $t->template_number }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-slate-800 uppercase">{{ $t->examination_name }}</div>
                                <div class="text-muted small">Update: {{ $t->updated_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="text-muted small">
                                {{ \Illuminate\Support\Str::limit(strip_tags($t->expertise), 60) }}
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <button type="button"
                                        class="btn btn-outline-primary medizen-btn-action-minimal edit-template-btn"
                                        data-template='@json($t)' title="Edit">
                                        <i data-feather="edit-2" style="width: 12px;"></i>
                                    </button>
                                    <form action="{{ route('master.templates.destroy', $t) }}" method="POST"
                                        class="d-inline swal-confirm" data-swal-title="Hapus Template?"
                                        data-swal-text="Hapus {{ $t->template_number }}?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger medizen-btn-action-minimal"
                                            title="Delete">
                                            <i data-feather="trash-2" style="width: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 uppercase text-muted small">BELUM ADA DATA TEMPLATE</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="p-2 border-top d-flex justify-content-between align-items-center bg-light-soft">
                <div class="text-muted" style="font-size: 10px; font-weight: 700; letter-spacing: 0.5px;">
                    SHOWING {{ $templates->firstItem() }} - {{ $templates->lastItem() }} OF {{ $templates->total() }} TEMPLATES
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        @if($templates->onFirstPage())
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">PREV</span></li>
                        @else
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $templates->appends(request()->query())->previousPageUrl() }}">PREV</a></li>
                        @endif
                        
                        @foreach($templates->getUrlRange(max(1, $templates->currentPage() - 1), min($templates->lastPage(), $templates->currentPage() + 1)) as $page => $url)
                            @if($page == $templates->currentPage())
                                <li class="page-item active"><span class="page-link rounded-0 border-0 bg-dark shadow-none" style="font-size: 10px; font-weight: bold;">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark" style="font-size: 10px;" href="{{ $templates->appends(request()->query())->url($page) }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        @if($templates->hasMorePages())
                            <li class="page-item"><a class="page-link rounded-0 border-0 shadow-none bg-white text-dark fw-bold" style="font-size: 10px;" href="{{ $templates->appends(request()->query())->nextPageUrl() }}">NEXT</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link rounded-0 border-0 bg-transparent text-muted" style="font-size: 10px;">NEXT</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
    </div>

    <!-- Modal Template -->
    <div class="modal fade" id="templateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content medizen-card-minimal border-0">
                <div class="modal-header border-bottom bg-light-soft p-3">
                    <h6 class="modal-title fw-bold text-slate-800 uppercase" id="modalTemplateTitle"
                        style="font-size: 13px; letter-spacing: 1px;">
                        DEFINE NEW REPORT RESOURCE
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="templateForm" method="POST">
                    @csrf
                    <div id="methodField"></div>
                    <div class="modal-body p-4">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="medizen-label-minimal">REFERENCE ID <span class="text-danger">*</span></label>
                                <input type="text" name="template_number" id="template_number"
                                    class="form-control medizen-input-minimal" required placeholder="e.g. TMP-01">
                            </div>
                            <div class="col-md-9">
                                <label class="medizen-label-minimal">EXAMINATION CATEGORY NAME <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="examination_name" id="examination_name"
                                    class="form-control medizen-input-minimal" required
                                    placeholder="Enter examination name...">
                            </div>
                        </div>
                        <div class="mb-0">
                            <label class="medizen-label-minimal mb-1">EXPERTISE DRAFT TEMPLATE <span
                                    class="text-danger">*</span></label>
                            <textarea name="expertise" id="expertise"
                                class="form-control medizen-input-minimal font-monospace" rows="12" required
                                style="font-size: 13px !important;"
                                placeholder="Write expertise template here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer p-3 border-top d-flex gap-2">
                        <button type="button" class="btn btn-secondary medizen-btn-minimal"
                            data-bs-dismiss="modal">DISCARD</button>
                        <button type="submit" class="btn btn-emerald medizen-btn-minimal">
                            <i data-feather="check" class="me-1" style="width: 14px;"></i> COMMIT CHANGES
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openAddModal() {
            $('#templateForm').attr('action', "{{ route('master.templates.store') }}");
            $('#methodField').empty();
            $('#modalTemplateTitle').text('DEFINE NEW REPORT RESOURCE');
            $('#template_number').val('');
            $('#examination_name').val('');
            $('#expertise').val('');
            $('#templateModal').modal('show');
        }

        $(document).on('click', '.edit-template-btn', function () {
            const t = $(this).data('template');
            $('#templateForm').attr('action', "{{ route('master.templates.update', ':id') }}".replace(':id', t.id));
            $('#methodField').html('@method('PUT')');
            $('#modalTemplateTitle').text('MODIFY REPORT TEMPLATE');
            $('#template_number').val(t.template_number);
            $('#examination_name').val(t.examination_name);
            $('#expertise').val(t.expertise);
            $('#templateModal').modal('show');
        });

        @if($errors->any())
            $(document).ready(function () {
                $('#templateModal').modal('show');
            });
        @endif
    </script>
@endpush