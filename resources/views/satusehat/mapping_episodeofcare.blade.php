@extends('layouts.app')
@section('content')
<div class="card card-medizen rounded-0 border-0 shadow-none">
    <div class="card-header bg-white border-bottom rounded-0">
        <h5 class="mb-0 fw-bold text-slate-800">Mapping Episode Of Care</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info rounded-0 small">Manajemen Mapping Episode Of Care.</div>
        <table class="table table-medizen small mt-3">
            <thead>
                <tr>
                    <th>Nama Episode</th>
                    <th>ID FHIR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mappings as $row)
                <tr>
                    <td>{{ $row->nama_episode }}</td>
                    <td><code>{{ $row->id_fhir_episode }}</code></td>
                </tr>
                @empty
                <tr><td colspan="2" class="text-center">Belum ada mapping.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $mappings->links() }}
    </div>
</div>
@endsection
