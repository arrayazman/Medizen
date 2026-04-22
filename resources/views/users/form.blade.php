@extends('layouts.app')
@section('title', isset($user) ? 'Edit User' : 'Tambah User')
@section('page-title', isset($user) ? 'Edit User' : 'Tambah User')
@section('content')
<div class="row justify-content-center"><div class="col-lg-8"><div class="card">
    <div class="card-header">{{ isset($user) ? 'Edit User' : 'Tambah User Baru' }}</div>
    <div class="card-body">
        <form method="POST" action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}">
            @csrf @if(isset($user)) @method('PUT') @endif
            <div class="mb-3"><label class="form-label">Nama <span class="text-danger">*</span></label><input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 mb-3"><label class="form-label">Role <span class="text-danger">*</span></label><select name="role" class="form-select" required>@foreach($roles as $r)<option value="{{ $r }}" {{ old('role', $user->role ?? '') == $r ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $r)) }}</option>@endforeach</select></div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Password {{ isset($user) ? '(kosongkan jika tidak diubah)' : '' }} {{ !isset($user) ? '*' : '' }}</label><input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ !isset($user) ? 'required' : '' }}>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6 mb-3"><label class="form-label">Konfirmasi Password</label><input type="password" name="password_confirmation" class="form-control"></div>
            </div>
            <div class="mb-3"><label class="form-label">Telepon</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone ?? '') }}"></div>
            @if(isset($user))
            <div class="mb-3"><div class="form-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="form-check-input" {{ old('is_active', $user->is_active) ? 'checked' : '' }}><label class="form-check-label">Aktif</label></div></div>
            @endif
            <hr><button type="submit" class="btn btn-primary">Simpan</button> <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div></div></div>
@endsection
