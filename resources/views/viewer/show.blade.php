@extends('layouts.app')
@section('title', 'DICOM Viewer')
@section('page-title', 'DICOM Viewer')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0">DICOM Viewer - {{ $order->patient->nama ?? 'Unknown' }}</h1>
    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">Kembali ke Order</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <iframe src="{{ $viewerUrl }}" style="width:100%; height:75vh; border:none; border-radius: 0 0 12px 12px;"></iframe>
    </div>
</div>
@endsection
