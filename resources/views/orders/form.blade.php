@extends('layouts.app')
@section('title', isset($order) ? 'Edit Order' : 'Buat Order')
@section('page-title', isset($order) ? 'Clinical Request Update' : 'New Radiology Request')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm" style="border-radius: 4px;">
                <div class="card-header bg-white border-bottom-0 pt-3 px-3 pb-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.85rem;">
                            {{ isset($order) ? 'AUTHORIZE ORDER MODIFICATION' : 'INITIATE CLINICAL REQUEST' }}
                        </h6>
                        <span class="badge bg-emerald-soft text-emerald " style="font-size: 0.55rem;">FORM ID:
                            {{ isset($order) ? 'REQ-' . $order->order_number : 'NEW_SESSION' }}</span>
                    </div>
                    <hr class="mt-2 mb-0 opacity-10">
                </div>

                <div class="card-body px-3 py-3">
                    <form method="POST"
                        action="{{ isset($order) ? route('orders.update', $order) : route('orders.store') }}">
                        @csrf
                        @if(isset($order)) @method('PUT') @endif

                        {{-- Section: Patient Context --}}
                        <div class="mb-3">
                            <div class="bg-light-soft p-2 rounded-1">
                                <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Patient
                                    Identification <span class="text-danger">*</span></label>
                                <select name="patient_id" id="patientSelect"
                                    class="form-select border-0 @error('patient_id') is-invalid @enderror" required
                                    style="font-size: 0.8rem;">
                                    <option value="">Search by Name, RM, or ID Number...</option>
                                    @if(isset($order) && $order->patient)
                                        <option value="{{ $order->patient_id }}" selected>{{ $order->patient->no_rm }} -
                                            {{ $order->patient->nama }}
                                        </option>
                                    @endif
                                    @if(request('patient_id'))
                                        @php $p = \App\Models\Patient::find(request('patient_id')); @endphp
                                        @if($p)
                                            <option value="{{ $p->id }}" selected>{{ $p->no_rm }} - {{ $p->nama }}</option>
                                        @endif
                                    @endif
                                </select>
                                @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="mt-1">
                                    <small class="text-muted" style="font-size: 0.65rem;">Reference unmatched? <a
                                            href="{{ route('patients.create') }}"
                                            class="text-emerald text-decoration-none fw-bold" target="_blank">REGISTER NEW
                                            PATIENT <i data-feather="external-link" style="width: 8px;"></i></a></small>
                                </div>
                            </div>
                        </div>

                        {{-- Section: Examination Specs --}}
                        <div class="mb-3 border-top pt-3">
                            <div class="row g-2">
                                <input type="hidden" name="modality" id="modalityHidden" value="{{ old('modality', $order->modality ?? '') }}">
                                
                                <div class="col-md-8">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Exam
                                        Classification <span class="text-danger">*</span></label>
                                    <select name="examination_type_id" id="examTypeSelect" class="form-select border-0 bg-light-soft"
                                        required style="font-size: 0.8rem; height: 32px;">
                                        <option value="">-- PILIH PEMERIKSAAN --</option>
                                        @foreach($examinationTypes as $e)
                                            <option value="{{ $e->id }}" 
                                                data-modality="{{ $e->modality->code }}" 
                                                {{ old('examination_type_id', $order->examination_type_id ?? '') == $e->id ? 'selected' : '' }}>
                                                [{{ $e->modality->code }}] {{ $e->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Station AE
                                        Title</label>
                                    <input type="text" name="station_ae_title" id="aeTitleInput"
                                        class="form-control border-0 bg-light-soft px-2"
                                        value="{{ old('station_ae_title', $order->station_ae_title ?? '') }}"
                                        style="font-size: 0.8rem; height: 32px;" placeholder="PACS NODE ID">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Schedule
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date" name="scheduled_date"
                                        class="form-control border-0 bg-light-soft px-2"
                                        value="{{ old('scheduled_date', isset($order) ? $order->scheduled_date->format('Y-m-d') : date('Y-m-d')) }}"
                                        required style="font-size: 0.8rem; height: 32px;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Schedule
                                        Time <span class="text-danger">*</span></label>
                                    <input type="time" name="scheduled_time"
                                        class="form-control border-0 bg-light-soft px-2"
                                        value="{{ old('scheduled_time', $order->scheduled_time ?? date('H:i')) }}" required
                                        style="font-size: 0.8rem; height: 32px;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Priority
                                        Level <span class="text-danger">*</span></label>
                                    <select name="priority" class="form-select border-0 bg-light-soft" required
                                        style="font-size: 0.8rem; height: 32px;">
                                        <option value="ROUTINE" {{ old('priority', $order->priority ?? '') == 'ROUTINE' ? 'selected' : '' }}>ROUTINE</option>
                                        <option value="URGENT" {{ old('priority', $order->priority ?? '') == 'URGENT' ? 'selected' : '' }}>URGENT</option>
                                        <option value="STAT" {{ old('priority', $order->priority ?? '') == 'STAT' ? 'selected' : '' }}>STAT (CITO)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Section: Clinical & Logistical --}}
                        <div class="mb-3 border-top pt-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Requesting
                                        Personnel</label>
                                    <select name="referring_doctor_id" class="form-select border-0 bg-light-soft"
                                        style="font-size: 0.8rem; height: 32px;">
                                        <option value="">Assigned Physician</option>
                                        @foreach($doctors as $d)
                                            <option value="{{ $d->id }}" {{ old('referring_doctor_id', $order->referring_doctor_id ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Assigned
                                        Radiographer</label>
                                    <select name="radiographer_id" class="form-select border-0 bg-light-soft"
                                        style="font-size: 0.8rem; height: 32px;">
                                        <option value="">Assigned Radiographer</option>
                                        @foreach($radiographers as $r)
                                            <option value="{{ $r->id }}" {{ old('radiographer_id', $order->radiographer_id ?? '') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Assigned
                                        Unit / Room</label>
                                    <select name="room_id" class="form-select border-0 bg-light-soft"
                                        style="font-size: 0.8rem; height: 32px;">
                                        <option value="">Assigned Unit</option>
                                        @foreach($rooms as $r)
                                            <option value="{{ $r->id }}" {{ old('room_id', $order->room_id ?? '') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Clinical
                                        Indication</label>
                                    <input type="text" name="clinical_info" class="form-control border-0 bg-light-soft px-2"
                                        value="{{ old('clinical_info', $order->clinical_info ?? '') }}"
                                        style="font-size: 0.8rem; height: 32px;" placeholder="Summarize indications...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold mb-1" style="font-size: 0.7rem;">Special
                                        Notes</label>
                                    <textarea name="notes" class="form-control border-0 bg-light-soft px-2" rows="2"
                                        style="font-size: 0.8rem;"
                                        placeholder="Additional instructions...">{{ old('notes', $order->notes ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top mt-3 pt-3">
                            <a href="{{ route('orders.index') }}" class="btn btn-light border btn-sm px-3"
                                style="border-radius: 2px; font-size: 0.7rem; height: 32px; display: flex; align-items: center;">ABORT</a>
                            <button type="submit" class="btn btn-emerald btn-sm px-4 shadow-sm"
                                style="border-radius: 2px; font-size: 0.7rem; height: 32px;">
                                <i data-feather="check" class="me-1" style="width: 14px; height: 14px;"></i>
                                {{ isset($order) ? 'UPDATE ORDER' : 'AUTHORIZE ORDER' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .bg-light-soft {
            background-color: #f8fafc;
        }

        .text-emerald {
            color: #10b981 !important;
        }

        .btn-emerald {
            background-color: #10b981;
            color: #fff;
            border: none;
            transition: all 0.2s;
        }

        .btn-emerald:hover {
            background-color: #059669;
            color: #fff;
            transform: translateY(-1px);
        }

        .bg-emerald-soft {
            background-color: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
        }

        /* Select2 Bootstrap 5 Theme Overrides */
        .select2-container--bootstrap-5 .select2-selection {
            background-color: #f8fafc !important;
            border: none !important;
            font-size: 0.85rem !important;
            min-height: 38px !important;
            display: flex !important;
            align-items: center !important;
        }

        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
            padding-left: 0.75rem !important;
        }
    </style>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Select2 basic for standard selects
            $('.form-select:not(#patientSelect)').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Select2 with AJAX for Patient
            $('#patientSelect').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Search by Name, RM, or ID Number...',
                allowClear: true,
                ajax: {
                    url: '{{ route("patients.search") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term };
                    },
                    processResults: function (data) {
                        return {
                            results: data.map(function (p) {
                                return {
                                    id: p.id,
                                    text: p.no_rm + ' - ' + p.nama + (p.nik ? ' (' + p.nik + ')' : '')
                                };
                            })
                        };
                    },
                    cache: true
                }
            });

            // Derived logic from Examination Type
            $('#examTypeSelect').on('change', function () {
                const selected = $(this).find(':selected');
                const modality = selected.data('modality');
                
                if (modality) {
                    $('#modalityHidden').val(modality);
                    // Use only modality code for AE Title as requested (remove _RSI etc)
                    $('#aeTitleInput').val(modality);
                }
            });
        });
    </script>
@endpush