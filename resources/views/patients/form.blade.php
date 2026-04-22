@extends('layouts.app')

@section('title', isset($patient) ? 'Edit Pasien' : 'Tambah Pasien')
@section('page-title', isset($patient) ? 'Pendaftaran Pasien' : 'Pendaftaran Pasien Baru')@section('content')


    <div class="row">
        <div class="col-12">
            <form method="POST"
                action="{{ isset($patient) ? route('patients.update', $patient) : route('patients.store') }}"
                id="patientForm">
                @csrf
                @if(isset($patient)) @method('PUT') @endif

                <div class="row">
                    <!-- LEFT COLUMN -->
                    <div class="col-lg-6">
                        <div class="medizen-card-minimal p-3 mb-4">
                            <div class="card-body p-0">

                                <!-- 1. Identitas Utama Pasien -->
                                <div class="medizen-form-group-title"><i data-feather="user"></i> Identitas Utama Pasien
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">No. RM</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="text" name="no_rm" id="no_rm_input"
                                                class="medizen-input-minimal bg-light fw-bold text-primary @error('no_rm') is-invalid @enderror"
                                                value="{{ old('no_rm', $patient->no_rm ?? $nextRm ?? '') }}"
                                                placeholder="AUTO/MANUAL" required>
                                            @if(!isset($patient))
                                                <div class="input-group-text py-0">
                                                    <div class="form-check mb-0">
                                                        <input class="form-check-input" type="checkbox" id="auto_rm" checked>
                                                        <label class="form-check-label small" for="auto_rm">Auto</label>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Nama Pasien <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" name="nama"
                                            class="medizen-input-minimal @error('nama') is-invalid @enderror"
                                            value="{{ old('nama', $patient->nama ?? '') }}" required
                                            placeholder="NAMA LENGKAP SESUAI KTP">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold text-nowrap">JK / Goldar / Nikah</label>
                                    <div class="col-sm-3">
                                        <select name="jenis_kelamin"
                                            class="medizen-input-minimal @error('jenis_kelamin') is-invalid @enderror"
                                            required>
                                            <option value="">JK</option>
                                            <option value="L" {{ old('jenis_kelamin', $patient->jenis_kelamin ?? '') == 'L' ? 'selected' : '' }}>LAKI-LAKI</option>
                                            <option value="P" {{ old('jenis_kelamin', $patient->jenis_kelamin ?? '') == 'P' ? 'selected' : '' }}>PEREMPUAN</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-2 px-0">
                                        <select name="gol_darah" class="medizen-input-minimal">
                                            <option value="-">DRH</option>
                                            @foreach(['A', 'B', 'AB', 'O'] as $g)
                                                <option value="{{ $g }}" {{ old('gol_darah', $patient->gol_darah ?? '') == $g ? 'selected' : '' }}>{{ $g }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4 ps-2">
                                        <select name="status_nikah" class="medizen-input-minimal">
                                            @foreach(['BELUM MENIKAH', 'MENIKAH', 'DUDA', 'JANDA'] as $sn)
                                                <option value="{{ $sn }}" {{ old('status_nikah', $patient->status_nikah ?? '') == $sn ? 'selected' : '' }}>{{ $sn }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Agama / Pend.</label>
                                    <div class="col-sm-5">
                                        <select name="agama" class="medizen-input-minimal">
                                            @foreach(['ISLAM', 'KRISTEN', 'KATOLIK', 'HINDU', 'BUDHA', 'KONGHUCU'] as $a)
                                                <option value="{{ $a }}" {{ old('agama', $patient->agama ?? '') == $a ? 'selected' : '' }}>{{ $a }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-4">
                                        <select name="pendidikan" class="medizen-input-minimal">
                                            <option value="-">PENDIDIKAN</option>
                                            @foreach(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'TIDAK SEKOLAH'] as $p)
                                                <option value="{{ $p }}" {{ old('pendidikan', $patient->pendidikan ?? '') == $p ? 'selected' : '' }}>{{ $p }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Tmp / Tgl Lahir</label>
                                    <div class="col-sm-4">
                                        <input type="text" name="tempat_lahir" class="medizen-input-minimal"
                                            value="{{ old('tempat_lahir', $patient->tempat_lahir ?? '') }}"
                                            placeholder="TEMPAT LAHIR">
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="date" name="tgl_lahir" id="tgl_lahir" class="medizen-input-minimal"
                                            value="{{ old('tgl_lahir', isset($patient) && $patient->tgl_lahir ? $patient->tgl_lahir->format('Y-m-d') : '') }}"
                                            onchange="calculateAge()">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Umur Pasien</label>
                                    <div class="col-sm-9">
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="age_y"
                                                        class="form-control text-center bg-light" readonly>
                                                    <span class="input-group-text py-0 px-2 small">Thn</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="age_m"
                                                        class="form-control text-center bg-light" readonly>
                                                    <span class="input-group-text py-0 px-2 small">Bln</span>
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <div class="input-group input-group-sm">
                                                    <input type="number" id="age_d"
                                                        class="form-control text-center bg-light" readonly>
                                                    <span class="input-group-text py-0 px-2 small">Hari</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Nama Ibu Kandung</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="nama_ibu" class="medizen-input-minimal"
                                            value="{{ old('nama_ibu', $patient->nama_ibu ?? '') }}"
                                            placeholder="NAMA KANDUNG IBU">
                                    </div>
                                </div>

                                <!-- 2. Alamat & Domisili -->
                                <div class="medizen-form-group-title mt-4"><i data-feather="map-pin"></i> Alamat & Domisili
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Alamat Jalan</label>
                                    <div class="col-sm-9">
                                        <textarea name="alamat" class="medizen-input-minimal mb-2" rows="2"
                                            placeholder="ALAMAT JALAN / NOMOR RUMAH">{{ old('alamat', $patient->alamat ?? '') }}</textarea>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Provinsi</label>
                                                    <select name="provinsi" id="select_provinsi"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="PROVINSI">
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kabupaten</label>
                                                    <select name="kabupaten" id="select_kabupaten"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KABUPATEN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kecamatan</label>
                                                    <select name="kecamatan" id="select_kecamatan"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KECAMATAN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kelurahan</label>
                                                    <select name="kelurahan" id="select_kelurahan"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KELURAHAN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <!-- 3. Penanggung Jawab -->
                                <div class="medizen-form-group-title"><i data-feather="shield"></i> Penanggung Jawab</div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold font-sm">Png Jwb / Nama</label>
                                    <div class="col-sm-3">
                                        <select name="png_jawab" class="medizen-input-minimal">
                                            @foreach(['DIRI SENDIRI', 'ORANG TUA', 'SUAMI', 'ISTRI', 'ANAK', 'KERABAT/SAUDARA'] as $pj)
                                                <option value="{{ $pj }}" {{ old('png_jawab', $patient->png_jawab ?? '') == $pj ? 'selected' : '' }}>{{ $pj }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" name="nama_pj" class="medizen-input-minimal"
                                            value="{{ old('nama_pj', $patient->nama_pj ?? '') }}"
                                            placeholder="NAMA LENGKAP P.J.">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Pekerjaan P.J.</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="pekerjaan_pj" class="medizen-input-minimal"
                                            value="{{ old('pekerjaan_pj', $patient->pekerjaan_pj ?? '') }}"
                                            placeholder="PEKERJAAN P.J.">
                                    </div>
                                </div>

                                <hr class="my-3 opacity-25">

                                <div class="row mb-1">
                                    <div class="col-sm-3"><label class="col-form-label fw-bold">Alamat P.J.</label></div>
                                    <div class="col-sm-9">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sync_alamat_pj">
                                            <label class="form-check-label small fw-bold text-primary" for="sync_alamat_pj">
                                                <i data-feather="copy" style="width:12px; margin-top:-3px"></i> Sama dengan
                                                Alamat Pasien
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-sm-3"></div>
                                    <div class="col-sm-9">
                                        <textarea name="alamat_pj" id="alamat_pj" class="medizen-input-minimal mb-2"
                                            rows="2"
                                            placeholder="ALAMAT JALAN P.J.">{{ old('alamat_pj', $patient->alamat_pj ?? '') }}</textarea>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Provinsi P.J.</label>
                                                    <select name="provinsi_pj" id="select_provinsi_pj"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="PROVINSI">
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kabupaten P.J.</label>
                                                    <select name="kabupaten_pj" id="select_kabupaten_pj"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KABUPATEN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kecamatan P.J.</label>
                                                    <select name="kecamatan_pj" id="select_kecamatan_pj"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KECAMATAN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-1">
                                                    <label class="small fw-bold text-muted">Kelurahan P.J.</label>
                                                    <select name="kelurahan_pj" id="select_kelurahan_pj"
                                                        class="medizen-input-minimal select2-region"
                                                        data-placeholder="KELURAHAN" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">


                                <!-- 4. Kontak & Pekerjaan -->
                                <div class="medizen-form-group-title mt-4"><i data-feather="settings"></i> Kontak &
                                    Pekerjaan</div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold text-nowrap">Pekerjaan / NIK</label>
                                    <div class="col-sm-4">
                                        <input type="text" name="pekerjaan" class="medizen-input-minimal"
                                            value="{{ old('pekerjaan', $patient->pekerjaan ?? '') }}"
                                            placeholder="PEKERJAAN">
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="text" name="nik" class="medizen-input-minimal"
                                            value="{{ old('nik', $patient->nik ?? '') }}" placeholder="NIK (KTP)">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold font-sm">Asuransi / No.</label>
                                    <div class="col-sm-4">
                                        <input type="text" name="asuransi" class="medizen-input-minimal"
                                            value="{{ old('asuransi', $patient->asuransi ?? '') }}"
                                            placeholder="JENIS ASURANSI">
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="text" name="no_peserta" class="medizen-input-minimal"
                                            value="{{ old('no_peserta', $patient->no_peserta ?? '') }}"
                                            placeholder="NOMOR PESERTA">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Email / No HP</label>
                                    <div class="col-sm-4">
                                        <input type="email" name="email" class="medizen-input-minimal"
                                            value="{{ old('email', $patient->email ?? '') }}" placeholder="EMAIL PASIEN">
                                    </div>
                                    <div class="col-sm-5">
                                        <input type="text" name="no_hp" class="medizen-input-minimal"
                                            value="{{ old('no_hp', $patient->no_hp ?? '') }}" placeholder="NO HP / WA">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Tgl Daftar</label>
                                    <div class="col-sm-9">
                                        <input type="date" name="tgl_daftar" class="medizen-input-minimal"
                                            value="{{ old('tgl_daftar', isset($patient) && $patient->tgl_daftar ? $patient->tgl_daftar->format('Y-m-d') : date('Y-m-d')) }}">
                                    </div>
                                </div>

                                <!-- 5. Sosial & Budaya -->
                                <div class="medizen-form-group-title mt-3"><i data-feather="globe"></i> Sosial & Budaya
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold">Suku & Bahasa</label>
                                    <div class="col-sm-4 pe-1">
                                        <input type="text" name="suku_bangsa" class="medizen-input-minimal"
                                            value="{{ old('suku_bangsa', $patient->suku_bangsa ?? '') }}"
                                            placeholder="SUKU">
                                    </div>
                                    <div class="col-sm-5 ps-1">
                                        <input type="text" name="bahasa" class="medizen-input-minimal"
                                            value="{{ old('bahasa', $patient->bahasa ?? '') }}" placeholder="BAHASA">
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <label class="col-sm-3 col-form-label fw-bold text-nowrap">Cacat Fisik</label>
                                    <div class="col-sm-9">
                                        <input type="text" name="cacat_fisik" class="medizen-input-minimal"
                                            value="{{ old('cacat_fisik', $patient->cacat_fisik ?? '') }}"
                                            placeholder="KETERANGAN CACAT">
                                    </div>
                                </div>

                                <!-- 6. Keanggotaan & Instansi -->
                                <div class="medizen-form-group-title mt-3"><i data-feather="briefcase"></i> Keanggotaan &
                                    Instansi
                                </div>

                                <div class="row mb-3">
                                    <label class="col-sm-3 col-form-label fw-bold">Instansi / NIP</label>
                                    <div class="col-sm-4 pe-1">
                                        <input type="text" name="instansi_pasien" class="medizen-input-minimal"
                                            value="{{ old('instansi_pasien', $patient->instansi_pasien ?? '') }}"
                                            placeholder="PT / INSTANSI">
                                    </div>
                                    <div class="col-sm-5 ps-1">
                                        <input type="text" name="nip_nrp" class="medizen-input-minimal"
                                            value="{{ old('nip_nrp', $patient->nip_nrp ?? '') }}" placeholder="NIP/NRP">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0 mb-3">
                                            <div class="card-body p-3">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="is_tni"
                                                        id="is_tni" value="1" {{ old('is_tni', $patient->is_tni ?? 0) ? 'checked' : '' }} onchange="toggleMilSection('tni')">
                                                    <label class="form-check-label fw-bold" for="is_tni">Anggota TNI</label>
                                                </div>
                                                <div id="section_tni" class="mil-section"
                                                    style="background: white; border-left: 3px solid #198754;">
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Golongan</label>
                                                        <div class="col-sm-8"><input type="text" name="tni_golongan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('tni_golongan', $patient->tni_golongan ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Kesatuan</label>
                                                        <div class="col-sm-8"><input type="text" name="tni_kesatuan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('tni_kesatuan', $patient->tni_kesatuan ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Pangkat</label>
                                                        <div class="col-sm-8"><input type="text" name="tni_pangkat"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('tni_pangkat', $patient->tni_pangkat ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <label class="col-sm-4 col-form-label small">Jabatan</label>
                                                        <div class="col-sm-8"><input type="text" name="tni_jabatan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('tni_jabatan', $patient->tni_jabatan ?? '') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0 mb-3">
                                            <div class="card-body p-3">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="is_polri"
                                                        id="is_polri" value="1" {{ old('is_polri', $patient->is_polri ?? 0) ? 'checked' : '' }} onchange="toggleMilSection('polri')">
                                                    <label class="form-check-label fw-bold" for="is_polri">Anggota
                                                        POLRI</label>
                                                </div>
                                                <div id="section_polri" class="mil-section"
                                                    style="background: white; border-left: 3px solid #0d6efd;">
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Golongan</label>
                                                        <div class="col-sm-8"><input type="text" name="polri_golongan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('polri_golongan', $patient->polri_golongan ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Kesatuan</label>
                                                        <div class="col-sm-8"><input type="text" name="polri_kesatuan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('polri_kesatuan', $patient->polri_kesatuan ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <label class="col-sm-4 col-form-label small">Pangkat</label>
                                                        <div class="col-sm-8"><input type="text" name="polri_pangkat"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('polri_pangkat', $patient->polri_pangkat ?? '') }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <label class="col-sm-4 col-form-label small">Jabatan</label>
                                                        <div class="col-sm-8"><input type="text" name="polri_jabatan"
                                                                class="form-control form-control-sm"
                                                                value="{{ old('polri_jabatan', $patient->polri_jabatan ?? '') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="medizen-card-minimal mb-5 bg-white">
                    <div class="card-body py-2 px-3 d-flex justify-content-end align-items-center">
                        <span class="text-muted font-monospace me-4" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                            <i data-feather="info" style="width:12px; height: 12px;" class="me-1"></i> MANDATORY FIELDS
                            MARKED WITH <span class="text-danger">*</span>
                        </span>
                        <a href="{{ route('patients.index') }}" class="btn btn-dark medizen-btn-minimal me-2">CANCEL</a>
                        <button type="submit" class="btn btn-emerald medizen-btn-minimal px-4">
                            <i data-feather="save" class="me-1" style="width: 14px; height: 14px;"></i>
                            COMMIT REGISTRATION
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
            function toggleMilSection(type) {
                const checkbox = document.getElementById('is_' + type);
                const section = document.getElementById('section_' + type);
                if (checkbox.checked) {
                    $(section).slideDown();
                } else {
                    $(section).slideUp();
                }
            }

            function calculateAge() {
                const dob = document.getElementById('tgl_lahir').value;
                if (!dob) return;

                const birthDate = new Date(dob);
                const today = new Date();

                let years = today.getFullYear() - birthDate.getFullYear();
                let months = today.getMonth() - birthDate.getMonth();
                let days = today.getDate() - birthDate.getDate();

                if (days < 0) {
                    months--;
                    days += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
                }
                if (months < 0) {
                    years--;
                    months += 12;
                }

                document.getElementById('age_y').value = years;
                document.getElementById('age_m').value = months;
                document.getElementById('age_d').value = days;
            }

            $(document).ready(function () {
                calculateAge();
                toggleMilSection('tni');
                toggleMilSection('polri');

                // Select2 for regions
                $('.select2-region').select2({
                    theme: 'bootstrap-5',
                    selectionCssClass: 'medizen-minimal-select2',
                    containerCssClass: 'medizen-minimal-select2',
                    allowClear: true
                });

                function setupRegionCascading(prefix, initialValues) {
                    const selectProv = $('#select_provinsi' + prefix);
                    const selectKab = $('#select_kabupaten' + prefix);
                    const selectKec = $('#select_kecamatan' + prefix);
                    const selectKel = $('#select_kelurahan' + prefix);

                    // Load Provinces
                    $.get("{{ route('regions.provinces') }}", function (data) {
                        selectProv.empty().append(new Option('', '', true, true));
                        data.forEach(p => {
                            selectProv.append(new Option(p.name, p.name, false, p.name == initialValues.provinsi));
                        });
                        if (initialValues.provinsi) {
                            selectProv.trigger('change');
                        }
                    });

                    // Province Change
                    selectProv.on('change', function (e, extra) {
                        let provinceName = $(this).val();
                        selectKab.empty().append(new Option('', '', true, true)).prop('disabled', true);
                        selectKec.empty().append(new Option('', '', true, true)).prop('disabled', true);
                        selectKel.empty().append(new Option('', '', true, true)).prop('disabled', true);

                        if (!provinceName) return;

                        $.get("{{ route('regions.provinces') }}", function (provinces) {
                            let province = provinces.find(p => p.name == provinceName);
                            if (province) {
                                $.get("/regions/regencies/" + province.id, function (data) {
                                    selectKab.prop('disabled', false);
                                    data.forEach(k => {
                                        let isSelected = (extra && extra.kabupaten) ? (k.name == extra.kabupaten) : (k.name == initialValues.kabupaten);
                                        selectKab.append(new Option(k.name, k.name, false, isSelected));
                                    });
                                    if (extra && extra.kabupaten) {
                                        selectKab.trigger('change', [extra]);
                                    } else if (initialValues.kabupaten) {
                                        selectKab.trigger('change');
                                    }
                                });
                            }
                        });
                    });

                    // Regency Change
                    selectKab.on('change', function (e, extra) {
                        let regencyName = $(this).val();
                        selectKec.empty().append(new Option('', '', true, true)).prop('disabled', true);
                        selectKel.empty().append(new Option('', '', true, true)).prop('disabled', true);

                        if (!regencyName) return;

                        let provinceName = selectProv.val();
                        $.get("{{ route('regions.provinces') }}", function (provinces) {
                            let province = provinces.find(p => p.name == provinceName);
                            if (province) {
                                $.get("/regions/regencies/" + province.id, function (regencies) {
                                    let regency = regencies.find(k => k.name == regencyName);
                                    if (regency) {
                                        $.get("/regions/districts/" + regency.id, function (data) {
                                            selectKec.prop('disabled', false);
                                            data.forEach(kc => {
                                                let isSelected = (extra && extra.kecamatan) ? (kc.name == extra.kecamatan) : (kc.name == initialValues.kecamatan);
                                                selectKec.append(new Option(kc.name, kc.name, false, isSelected));
                                            });
                                            if (extra && extra.kecamatan) {
                                                selectKec.trigger('change', [extra]);
                                            } else if (initialValues.kecamatan) {
                                                selectKec.trigger('change');
                                            }
                                        });
                                    }
                                });
                            }
                        });
                    });

                    // District Change
                    selectKec.on('change', function (e, extra) {
                        let districtName = $(this).val();
                        selectKel.empty().append(new Option('', '', true, true)).prop('disabled', true);

                        if (!districtName) return;

                        let provinceName = selectProv.val();
                        let regencyName = selectKab.val();

                        $.get("{{ route('regions.provinces') }}", function (provinces) {
                            let province = provinces.find(p => p.name == provinceName);
                            if (province) {
                                $.get("/regions/regencies/" + province.id, function (regencies) {
                                    let regency = regencies.find(k => k.name == regencyName);
                                    if (regency) {
                                        $.get("/regions/districts/" + regency.id, function (districts) {
                                            let district = districts.find(kc => kc.name == districtName);
                                            if (district) {
                                                $.get("/regions/villages/" + district.id, function (data) {
                                                    selectKel.prop('disabled', false);
                                                    data.forEach(kl => {
                                                        let isSelected = (extra && extra.kelurahan) ? (kl.name == extra.kelurahan) : (kl.name == initialValues.kelurahan);
                                                        selectKel.append(new Option(kl.name, kl.name, false, isSelected));
                                                    });
                                                });
                                            }
                                        });
                                    }
                                });
                            }
                        });
                    });
                }

                // Initialize Patient Regions
                setupRegionCascading('', {
                    provinsi: "{{ old('provinsi', $patient->provinsi ?? '') }}",
                    kabupaten: "{{ old('kabupaten', $patient->kabupaten ?? '') }}",
                    kecamatan: "{{ old('kecamatan', $patient->kecamatan ?? '') }}",
                    kelurahan: "{{ old('kelurahan', $patient->kelurahan ?? '') }}"
                });

                // Initialize PJ Regions
                setupRegionCascading('_pj', {
                    provinsi: "{{ old('provinsi_pj', $patient->provinsi_pj ?? '') }}",
                    kabupaten: "{{ old('kabupaten_pj', $patient->kabupaten_pj ?? '') }}",
                    kecamatan: "{{ old('kecamatan_pj', $patient->kecamatan_pj ?? '') }}",
                    kelurahan: "{{ old('kelurahan_pj', $patient->kelurahan_pj ?? '') }}"
                });

                // Sync Alamat PJ Logic
                $('#sync_alamat_pj').on('change', function () {
                    if (this.checked) {
                        const values = {
                            provinsi: $('#select_provinsi').val(),
                            kabupaten: $('#select_kabupaten').val(),
                            kecamatan: $('#select_kecamatan').val(),
                            kelurahan: $('#select_kelurahan').val()
                        };
                        const patientAlamat = $('textarea[name="alamat"]').val();

                        $('textarea[name="alamat_pj"]').val(patientAlamat);

                        // Trigger cascading sync for PJ with patient values
                        $('#select_provinsi_pj').val(values.provinsi).trigger('change', [values]);
                    }
                });

                // Handle Auto RM
                const nextRm = "{{ $nextRm ?? '' }}";
                $('#auto_rm').change(function () {
                    const input = $('input[name="no_rm"]');
                    if (this.checked) {
                        input.addClass('bg-light').attr('placeholder', 'Otomatis').prop('readonly', true);
                        if (nextRm) input.val(nextRm);
                    } else {
                        input.removeClass('bg-light').attr('placeholder', 'Input Manual').prop('readonly', false);
                        if (nextRm && input.val() === nextRm) input.val('');
                    }
                });
                if ($('#auto_rm').length) {
                    $('#auto_rm').trigger('change');
                }
            });
        </script>
@endpush