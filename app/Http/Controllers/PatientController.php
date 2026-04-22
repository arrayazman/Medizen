<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\AuditService;
use App\Models\Modality;
use App\Models\ExaminationType;
use App\Models\Doctor;
use App\Services\PACSClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $query = Patient::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $patients = $query->latest()->paginate(15);
        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        $lastRecord = \App\Models\LastMedicalRecord::first();
        $nextNumber = $lastRecord ? $lastRecord->last_number + 1 : 1;
        $nextRm = str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        return view('patients.form', compact('nextRm'));
    }

    public function store(Request $request)
    {
        // Check if auto_rm is true and no_rm is empty, or if we just want to accept user's auto input.
        // Actually, no_rm input might just contain the "nextRm" we passed to the view.

        $validated = $request->validate([
            'no_rm' => 'required|string|max:20|unique:patients',
            'nik' => 'nullable|string|max:20|unique:patients',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'gol_darah' => 'nullable|string|max:5',
            'tempat_lahir' => 'nullable|string|max:100',
            'tgl_lahir' => 'nullable|date',
            'pendidikan' => 'nullable|string|max:50',
            'nama_ibu' => 'nullable|string|max:100',
            'png_jawab' => 'nullable|string|max:50',
            'nama_pj' => 'nullable|string|max:100',
            'pekerjaan_pj' => 'nullable|string|max:100',
            'suku_bangsa' => 'nullable|string|max:50',
            'bahasa' => 'nullable|string|max:50',
            'cacat_fisik' => 'nullable|string|max:100',
            'is_tni' => 'nullable|boolean',
            'tni_golongan' => 'nullable|string|max:50',
            'tni_kesatuan' => 'nullable|string|max:100',
            'tni_pangkat' => 'nullable|string|max:50',
            'tni_jabatan' => 'nullable|string|max:100',
            'is_polri' => 'nullable|boolean',
            'polri_golongan' => 'nullable|string|max:50',
            'polri_kesatuan' => 'nullable|string|max:100',
            'polri_pangkat' => 'nullable|string|max:50',
            'polri_jabatan' => 'nullable|string|max:100',
            'agama' => 'nullable|string|max:20',
            'status_nikah' => 'nullable|string|max:20',
            'asuransi' => 'nullable|string|max:100',
            'no_peserta' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'no_hp' => 'nullable|string|max:20',
            'tgl_daftar' => 'nullable|date',
            'pekerjaan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'alamat_pj' => 'nullable|string',
            'kelurahan_pj' => 'nullable|string|max:100',
            'kecamatan_pj' => 'nullable|string|max:100',
            'kabupaten_pj' => 'nullable|string|max:100',
            'provinsi_pj' => 'nullable|string|max:100',
            'instansi_pasien' => 'nullable|string|max:150',
            'nip_nrp' => 'nullable|string|max:50',
        ]);

        $validated['is_tni'] = $request->boolean('is_tni');
        $validated['is_polri'] = $request->boolean('is_polri');

        $patient = Patient::create($validated);
        AuditService::logCreate($patient);

        // Update the last_medical_records
        if (is_numeric($validated['no_rm'])) {
            $numRm = (int) $validated['no_rm'];
            $lastRecord = \App\Models\LastMedicalRecord::first();
            if (!$lastRecord) {
                \App\Models\LastMedicalRecord::create(['last_number' => $numRm]);
            } elseif ($numRm > $lastRecord->last_number) {
                $lastRecord->update(['last_number' => $numRm]);
            }
        }

        return redirect()->route('patients.index')
            ->with('success', 'Pasien berhasil ditambahkan');
    }

    public function show(Patient $patient, \App\Services\PACSClient $PACS)
    {
        $patient->load('orders.examinationType', 'orders.referringDoctor');

        // Fetch PACS studies for this patient
        $PACSStudies = [];
        $result = $PACS->post('/tools/find', [
            'Level' => 'Patient',
            'Query' => ['PatientID' => $patient->no_rm],
            'Limit' => 1
        ]);

        if ($result['success'] && !empty($result['data'])) {
            $PACSPatientId = reset($result['data']); // Get the ID

            // Get patient details including study list
            $PACSPatient = $PACS->getPatient($PACSPatientId);
            if ($PACSPatient) {
                foreach ($PACSPatient['Studies'] ?? [] as $studyId) {
                    $study = $PACS->getStudy($studyId);
                    if ($study) {
                        $study['SeriesData'] = [];
                        // get thumbnails for series
                        foreach ($study['Series'] ?? [] as $seriesId) {
                            $seriesData = $PACS->getSeries($seriesId);
                            if ($seriesData && !empty($seriesData['Instances'])) {
                                $seriesData['_firstInstance'] = reset($seriesData['Instances']);
                                $seriesData['_instanceCount'] = count($seriesData['Instances']);
                                $study['SeriesData'][] = $seriesData;
                            }
                        }

                        // Sort series by InstanceNumber to keep it orderly
                        usort($study['SeriesData'], function ($a, $b) {
                            $numA = (int) ($a['MainDicomTags']['SeriesNumber'] ?? 0);
                            $numB = (int) ($b['MainDicomTags']['SeriesNumber'] ?? 0);
                            return $numA <=> $numB;
                        });

                        $PACSStudies[] = $study;
                    }
                }
            }
        }

        // Sort studies by date descending
        usort($PACSStudies, function ($a, $b) {
            $dateA = $a['MainDicomTags']['StudyDate'] ?? '';
            $dateB = $b['MainDicomTags']['StudyDate'] ?? '';
            return $dateB <=> $dateA;
        });

        $baseUrl = $PACS->getPublicUrl();
        $modalities = Modality::active()->get();
        $examinationTypes = ExaminationType::active()->with('modality')->get();
        $doctors = Doctor::active()->get();

        return view('patients.show', [
            'patient' => $patient,
            'PACSStudies' => $PACSStudies,
            'baseUrl' => $baseUrl,
            'modalities' => $modalities,
            'examinationTypes' => $examinationTypes,
            'doctors' => $doctors
        ]);
    }

    public function edit(Patient $patient)
    {
        return view('patients.form', compact('patient'));
    }

    public function update(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'no_rm' => 'required|string|max:20|unique:patients,no_rm,' . $patient->id,
            'nik' => 'nullable|string|max:20|unique:patients,nik,' . $patient->id,
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'gol_darah' => 'nullable|string|max:5',
            'tempat_lahir' => 'nullable|string|max:100',
            'tgl_lahir' => 'nullable|date',
            'pendidikan' => 'nullable|string|max:50',
            'nama_ibu' => 'nullable|string|max:100',
            'png_jawab' => 'nullable|string|max:50',
            'nama_pj' => 'nullable|string|max:100',
            'pekerjaan_pj' => 'nullable|string|max:100',
            'suku_bangsa' => 'nullable|string|max:50',
            'bahasa' => 'nullable|string|max:50',
            'cacat_fisik' => 'nullable|string|max:100',
            'is_tni' => 'nullable|boolean',
            'tni_golongan' => 'nullable|string|max:50',
            'tni_kesatuan' => 'nullable|string|max:100',
            'tni_pangkat' => 'nullable|string|max:50',
            'tni_jabatan' => 'nullable|string|max:100',
            'is_polri' => 'nullable|boolean',
            'polri_golongan' => 'nullable|string|max:50',
            'polri_kesatuan' => 'nullable|string|max:100',
            'polri_pangkat' => 'nullable|string|max:50',
            'polri_jabatan' => 'nullable|string|max:100',
            'agama' => 'nullable|string|max:20',
            'status_nikah' => 'nullable|string|max:20',
            'asuransi' => 'nullable|string|max:100',
            'no_peserta' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'no_hp' => 'nullable|string|max:20',
            'tgl_daftar' => 'nullable|date',
            'pekerjaan' => 'nullable|string|max:100',
            'alamat' => 'nullable|string',
            'kelurahan' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kabupaten' => 'nullable|string|max:100',
            'provinsi' => 'nullable|string|max:100',
            'alamat_pj' => 'nullable|string',
            'kelurahan_pj' => 'nullable|string|max:100',
            'kecamatan_pj' => 'nullable|string|max:100',
            'kabupaten_pj' => 'nullable|string|max:100',
            'provinsi_pj' => 'nullable|string|max:100',
            'instansi_pasien' => 'nullable|string|max:150',
            'nip_nrp' => 'nullable|string|max:50',
        ]);

        $validated['is_tni'] = $request->boolean('is_tni');
        $validated['is_polri'] = $request->boolean('is_polri');

        $old = $patient->toArray();
        $patient->update($validated);
        AuditService::logUpdate($patient, $old);

        return redirect()->route('patients.index')
            ->with('success', 'Data pasien berhasil diperbarui');
    }

    public function destroy(Patient $patient)
    {
        AuditService::logDelete($patient);
        $patient->delete();

        return redirect()->route('patients.index')
            ->with('success', 'Pasien berhasil dihapus');
    }

    // AJAX search for order form
    public function search(Request $request)
    {
        $query = Patient::query();
        if ($request->filled('q')) {
            $query->search($request->q);
        } else {
            $query->latest();
        }

        $patients = $query->take(10)->get();
        return response()->json($patients);
    }
}

