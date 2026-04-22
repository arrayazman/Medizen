<?php

namespace App\Http\Controllers;

use App\Services\PACSClient;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PACSController extends Controller
{
    protected PACSClient $PACS;
    protected \App\Services\OrthancService $orthanc;

    public function __construct(PACSClient $PACS, \App\Services\OrthancService $orthanc)
    {
        $this->PACS = $PACS;
        $this->orthanc = $orthanc;
    }

    public function worklists(Request $request)
    {
        $worklists = $this->PACS->getWorklists() ?? [];
        $isAvailable = $this->PACS->isAvailable();

        // Process details for each worklist if they are strings to enable filtering
        $allData = [];
        foreach ($worklists as $wl) {
            if (is_array($wl)) {
                $date = $wl['ScheduledProcedureStepStartDate'] ?? ($wl['Tags']['ScheduledProcedureStepStartDate'] ?? '-');
                $time = $wl['ScheduledProcedureStepStartTime'] ?? ($wl['Tags']['ScheduledProcedureStepStartTime'] ?? '000000');

                $item = [
                    'filename' => $wl['ID'] ?? ($wl['Name'] ?? 'Unknown'),
                    'patient_name' => str_replace('^', ', ', $wl['PatientName'] ?? ($wl['Tags']['PatientName'] ?? '-')),
                    'accession' => $wl['AccessionNumber'] ?? ($wl['Tags']['AccessionNumber'] ?? '-'),
                    'modality' => $wl['Modality'] ?? ($wl['Tags']['Modality'] ?? '-'),
                    'date' => $date,
                    'time' => $time,
                    'sort_key' => ($date !== '-' ? $date : '00000000') . ($time !== '-' ? $time : '000000'),
                    'raw' => $wl,
                ];
            } else {
                $parts = explode('_', str_replace('.wl', '', $wl));
                $accession = $parts[0] ?? '-';
                $timestamp = $parts[1] ?? null;
                $date = '-';
                $sort_key = '00000000000000';

                if ($timestamp && is_numeric($timestamp)) {
                    $date = date('Ymd', (int) $timestamp);
                    $time = date('His', (int) $timestamp);
                    $sort_key = date('YmdHis', (int) $timestamp);
                }

                $item = [
                    'filename' => $wl,
                    'patient_name' => 'Click download to view',
                    'accession' => $accession,
                    'modality' => '-',
                    'date' => $date,
                    'sort_key' => $sort_key,
                    'raw' => ['ID' => $wl],
                ];
            }
            $allData[] = $item;
        }

        // Apply Filters
        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $allData = array_filter($allData, function ($item) use ($search) {
                return str_contains(strtolower($item['patient_name'] ?? ''), $search) ||
                    str_contains(strtolower($item['accession'] ?? ''), $search) ||
                    str_contains(strtolower($item['filename'] ?? ''), $search);
            });
        }

        if ($request->filled('modality')) {
            $modality = strtoupper($request->modality);
            $allData = array_filter($allData, function ($item) use ($modality) {
                return ($item['modality'] ?? '-') === $modality;
            });
        }

        if ($request->has('reset')) {
            session()->forget('wl_filters');
            return redirect()->route('pacs.worklists'); // clean URL without parameters
        }

        // Session-based persistence (Replacement for LocalStorage)
        if (!$request->has('date_start') && !$request->has('search') && !$request->has('modality')) {
            if (session()->has('wl_filters')) {
                // Load from session
                $request->merge(session('wl_filters'));
            } else {
                // Default to Today
                $request->merge(['date_start' => date('Y-m-d'), 'date_end' => date('Y-m-d')]);
            }
        } else {
            // Save active filters to memory
            session(['wl_filters' => $request->only(['search', 'date_start', 'date_end', 'modality'])]);
        }

        $start = $request->filled('date_start') ? str_replace('-', '', $request->date_start) : '00000000';
        $end = $request->filled('date_end') ? str_replace('-', '', $request->date_end) : '99999999';

        if ($start !== '00000000' || $end !== '99999999') {
            $allData = array_filter($allData, function ($item) use ($start, $end) {
                $date = $item['date'] ?? '-';
                if ($date === '-') {
                    $parts = explode('_', str_replace('.wl', '', $item['filename'] ?? ''));
                    $timestamp = $parts[1] ?? null;
                    if ($timestamp && is_numeric($timestamp)) {
                        $date = date('Ymd', (int)$timestamp);
                    }
                }

                // Jika item sama sekali tidak memiliki Scheduled Date (sering terjadi pada Worklist),
                // kita loloskan terus (return true) agar statusnya selalu 'Aktif/Terbuka' dan bisa dicari.
                if ($date === '-') {
                    return true;
                }
                
                return $date >= $start && $date <= $end;
            });
        }

        // SORT BY DATE DESCENDING
        usort($allData, function ($a, $b) {
            return strcmp($b['sort_key'] ?? '', $a['sort_key'] ?? '');
        });

        $worklists = $allData;

        return view('pacs.worklists', compact('worklists', 'isAvailable'));
    }

    // ============================
    // DASHBOARD
    // ============================

    public function index()
    {
        $system = $this->PACS->getSystem();
        $statistics = $this->PACS->getStatistics();
        $isAvailable = $system !== null;
        $plugins = $isAvailable ? ($this->PACS->getPlugins() ?? []) : [];
        $modalities = $isAvailable ? ($this->PACS->getModalities() ?? []) : [];

        return view('pacs.index', compact('system', 'statistics', 'isAvailable', 'plugins', 'modalities'));
    }

    // ============================
    // PATIENTS
    // ============================

    public function patients(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 50;
        $hasFilters = $request->filled('search') || $request->filled('patient_id');

        if ($hasFilters) {
            $query = ['PatientName' => '*'];
            if ($request->filled('search')) {
                $query['PatientName'] = '*' . $request->search . '*';
            }
            if ($request->filled('patient_id')) {
                $query['PatientID'] = '*' . $request->patient_id . '*';
            }
            $result = $this->PACS->post('/tools/find', [
                'Level' => 'Patient',
                'Query' => $query,
                'Expand' => true,
                'Limit' => 0,
            ]);
            $allPatients = $result['success'] ? ($result['data'] ?? []) : [];
            // Sort by LastUpdate descending
            usort($allPatients, fn($a, $b) => ($b['LastUpdate'] ?? '') <=> ($a['LastUpdate'] ?? ''));
            $totalPatients = count($allPatients);
            $patients = array_slice($allPatients, ($page - 1) * $perPage, $perPage);
            $hasMore = ($page * $perPage) < $totalPatients;
        } else {
            // Reverse pagination: fetch from end so newest appears first
            $totalPatients = $this->PACS->getStatistics()['CountPatients'] ?? 0;
            $offset = max(0, $totalPatients - ($page * $perPage));
            $limit = ($page * $perPage > $totalPatients) ? ($totalPatients - ($page - 1) * $perPage) : $perPage;
            $patients = $this->PACS->getPatientsPaginated($offset, $limit) ?? [];
            $patients = array_reverse($patients); // Reverse so newest is first
            $hasMore = $offset > 0;
        }

        return view('pacs.patients', compact('patients', 'page', 'perPage', 'hasMore', 'totalPatients'));
    }

    public function patientDetail(string $id)
    {
        $patient = $this->PACS->getPatient($id);
        if (!$patient)
            return redirect()->route('pacs.patients')->with('error', 'Pasien tidak ditemukan');

        $patientId = $patient['MainDicomTags']['PatientID'] ?? null;
        $studies = [];
        
        if ($patientId) {
            $studies = $this->PACS->findStudies(['PatientID' => $patientId]) ?? [];
            
            // Sort by StudyDate descending (newest first)
            usort($studies, function ($a, $b) {
                $dateA = $a['MainDicomTags']['StudyDate'] ?? '';
                $dateB = $b['MainDicomTags']['StudyDate'] ?? '';
                if ($dateA === $dateB) {
                    $timeA = $a['MainDicomTags']['StudyTime'] ?? '';
                    $timeB = $b['MainDicomTags']['StudyTime'] ?? '';
                    return $timeB <=> $timeA;
                }
                return $dateB <=> $dateA;
            });

            // Enrich with statistics
            foreach ($studies as &$study) {
                $study['_statistics'] = $this->PACS->getStudyStatistics($study['ID']);
            }
        }

        $baseUrl = $this->PACS->getPublicUrl();
        return view('pacs.patient-detail', compact('patient', 'studies', 'baseUrl'));
    }

    public function deletePatient(string $id)
    {
        $patient = $this->PACS->getPatient($id);
        $result = $this->PACS->delete("/patients/{$id}");

        if ($result['success']) {
            AuditService::log(
                'DELETE',
                'PACSPatient',
                $id,
                null,
                null,
                'Pasien dihapus dari PACS: ' . ($patient['MainDicomTags']['PatientName'] ?? $id)
            );
            return redirect()->route('pacs.patients')->with('success', 'Pasien berhasil dihapus');
        }
        return redirect()->route('pacs.patients')->with('error', 'Gagal menghapus pasien');
    }

    public function syncPatient(string $id)
    {
        $PACSPatient = $this->PACS->getPatient($id);
        if (!$PACSPatient)
            return redirect()->back()->with('error', 'Pasien tidak ditemukan di PACS');

        $tags = $PACSPatient['MainDicomTags'] ?? [];
        $patientId = $tags['PatientID'] ?? null;

        if (!$patientId) {
            return redirect()->back()->with('error', 'Pasien di PACS tidak memiliki PatientID (No. RM)');
        }

        $patient = \App\Models\Patient::where('no_rm', $patientId)->first();
        $isNew = false;

        $name = str_replace('^', ', ', $tags['PatientName'] ?? 'Unknown');
        $sex = ($tags['PatientSex'] ?? '') === 'M' ? 'L' : (($tags['PatientSex'] ?? '') === 'F' ? 'P' : 'L');

        $birthDate = null;
        if (isset($tags['PatientBirthDate']) && strlen($tags['PatientBirthDate']) == 8) {
            try {
                $birthDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['PatientBirthDate'])->format('Y-m-d');
            } catch (\Exception $e) {
            }
        }

        if (!$patient) {
            $patient = \App\Models\Patient::create([
                'no_rm' => $patientId,
                'nama' => $name,
                'jenis_kelamin' => $sex,
                'tgl_lahir' => $birthDate,
            ]);
            $isNew = true;
            AuditService::log('CREATE', 'Patient', $patient->id, null, $patient->toArray(), 'Pasien disinkronisasi dari PACS');
        } else {
            $updates = [];
            if (!$patient->nama)
                $updates['nama'] = $name;
            if (!$patient->jenis_kelamin)
                $updates['jenis_kelamin'] = $sex;
            if (!$patient->tgl_lahir)
                $updates['tgl_lahir'] = $birthDate;

            if (!empty($updates)) {
                $old = $patient->toArray();
                $patient->update($updates);
                AuditService::log('UPDATE', 'Patient', $patient->id, $old, $patient->toArray(), 'Data pasien diupdate dari PACS');
            }
        }

        $msg = $isNew ? 'Pasien berhasil disinkronkan dan ditambahkan ke database RIS.' : 'Pasien sudah ada di database RIS, data diperbarui.';
        return redirect()->route('patients.edit', $patient->id)->with('success', $msg);
    }

    public function syncAllPatients(Request $request)
    {
        $page = max(1, (int) $request->get('sync_page', 1));
        $perPage = 100;
        $totalSynced = (int) $request->get('total_synced', 0);

        // Fetch paginated patients from PACS to avoid memory overflow
        $totalPACSPatients = $this->PACS->getStatistics()['CountPatients'] ?? 0;

        if ($totalPACSPatients == 0) {
            return redirect()->route('pacs.patients')->with('error', 'Tidak ada data pasien di pacs.');
        }

        $offset = ($page - 1) * $perPage;
        $patientsChunk = $this->PACS->getPatientsPaginated($offset, $perPage) ?? [];

        if (empty($patientsChunk)) {
            // Done syncing
            return redirect()->route('pacs.patients')->with('success', 'Sinkronisasi selesai. Total ' . number_format($totalSynced) . ' pasien berhasil disinkronkan.');
        }

        foreach ($patientsChunk as $PACSPatient) {
            $tags = $PACSPatient['MainDicomTags'] ?? [];
            $patientId = $tags['PatientID'] ?? null;

            if (!$patientId)
                continue;

            $patient = \App\Models\Patient::where('no_rm', $patientId)->first();

            $name = str_replace('^', ', ', $tags['PatientName'] ?? 'Unknown');
            $sex = ($tags['PatientSex'] ?? '') === 'M' ? 'L' : (($tags['PatientSex'] ?? '') === 'F' ? 'P' : 'L');

            $birthDate = null;
            if (isset($tags['PatientBirthDate']) && strlen($tags['PatientBirthDate']) == 8) {
                try {
                    $birthDate = \Carbon\Carbon::createFromFormat('Ymd', $tags['PatientBirthDate'])->format('Y-m-d');
                } catch (\Exception $e) {
                }
            }

            if (!$patient) {
                \App\Models\Patient::create([
                    'no_rm' => $patientId,
                    'nama' => $name,
                    'jenis_kelamin' => $sex,
                    'tgl_lahir' => $birthDate,
                ]);
            } else {
                $updates = [];
                if (!$patient->nama)
                    $updates['nama'] = $name;
                if (!$patient->jenis_kelamin)
                    $updates['jenis_kelamin'] = $sex;
                if (!$patient->tgl_lahir)
                    $updates['tgl_lahir'] = $birthDate;

                if (!empty($updates)) {
                    $patient->update($updates);
                }
            }
            $totalSynced++;
        }

        // Redirect to next page to continue syncing
        $nextPage = $page + 1;
        $percentage = round(($totalSynced / $totalPACSPatients) * 100);

        return redirect()->route('pacs.sync-all-patients', ['sync_page' => $nextPage, 'total_synced' => $totalSynced])
            ->with('info', "Sedang sinkronisasi... {$percentage}% ({$totalSynced} dari {$totalPACSPatients})");
    }

    // ============================
    // STUDIES
    // ============================

    public function studies(Request $request)
    {
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 50;

        // Build query - always use /tools/find for proper sorting
        $query = [];
        if ($request->filled('search')) {
            $query['PatientName'] = '*' . $request->search . '*';
        }
        if ($request->filled('date')) {
            $query['StudyDate'] = str_replace('-', '', $request->date);
        }
        if ($request->filled('modality')) {
            $query['ModalitiesInStudy'] = $request->modality;
        }
        if ($request->filled('accession')) {
            $query['AccessionNumber'] = '*' . $request->accession . '*';
        }

        // Default: show recent studies (last 2 years) to keep results manageable
        if (empty($query)) {
            $fromDate = now()->subYears(2)->format('Ymd');
            $toDate = now()->format('Ymd');
            $query['StudyDate'] = "{$fromDate}-{$toDate}";
        }

        $result = $this->PACS->post('/tools/find', [
            'Level' => 'Study',
            'Query' => $query,
            'Expand' => true,
        ]);
        $allStudies = $result['success'] ? ($result['data'] ?? []) : [];

        // Sort by StudyDate descending (newest first)
        usort($allStudies, function ($a, $b) {
            $dateA = $a['MainDicomTags']['StudyDate'] ?? '';
            $dateB = $b['MainDicomTags']['StudyDate'] ?? '';
            if ($dateA === $dateB) {
                // Secondary sort by StudyTime descending
                $timeA = $a['MainDicomTags']['StudyTime'] ?? '';
                $timeB = $b['MainDicomTags']['StudyTime'] ?? '';
                return $timeB <=> $timeA;
            }
            return $dateB <=> $dateA;
        });

        $totalStudies = count($allStudies);
        $studies = array_slice($allStudies, ($page - 1) * $perPage, $perPage);
        $hasMore = ($page * $perPage) < $totalStudies;
        $totalAll = $this->PACS->getStatistics()['CountStudies'] ?? $totalStudies;

        $pacsModalities = $this->PACS->getModalities() ?? [];

        return view('pacs.studies', compact('studies', 'page', 'perPage', 'hasMore', 'totalStudies', 'totalAll', 'pacsModalities'));
    }

    public function studyDetail(string $id)
    {
        $study = $this->PACS->getStudy($id);
        if (!$study)
            return redirect()->route('pacs.studies')->with('error', 'Study tidak ditemukan');

        $stats = $this->PACS->getStudyStatistics($id);

        $series = [];
        foreach ($study['Series'] ?? [] as $seriesId) {
            $seriesData = $this->PACS->getSeries($seriesId);
            if ($seriesData) {
                // Get first instance for thumbnail
                $instances = $seriesData['Instances'] ?? [];
                $seriesData['_firstInstance'] = !empty($instances) ? $instances[0] : null;
                $seriesData['_instanceCount'] = count($instances);
                $series[] = $seriesData;
            }
        }

        $baseUrl = $this->PACS->getPublicUrl();
        $studyUID = $study['MainDicomTags']['StudyInstanceUID'] ?? '';

        return view('pacs.study-detail', compact('study', 'series', 'stats', 'baseUrl', 'studyUID'));
    }

    public function deleteStudy(string $id)
    {
        $study = $this->PACS->getStudy($id);
        $result = $this->PACS->delete("/studies/{$id}");

        if ($result['success']) {
            AuditService::log(
                'DELETE',
                'PACSStudy',
                $id,
                null,
                null,
                'Study dihapus: ' . ($study['MainDicomTags']['StudyDescription'] ?? $id)
            );
            return redirect()->route('pacs.studies')->with('success', 'Study berhasil dihapus');
        }
        return redirect()->route('pacs.studies')->with('error', 'Gagal menghapus study');
    }

    public function modifyStudy(Request $request, string $id)
    {
        $study = $this->PACS->getStudy($id);
        if (!$study)
            return redirect()->route('pacs.studies')->with('error', 'Study tidak ditemukan');

        $patientReplace = [];
        $studyReplace = [];

        // Patient Level
        if ($request->filled('PatientName'))
            $patientReplace['PatientName'] = $request->PatientName;
        if ($request->filled('PatientID'))
            $patientReplace['PatientID'] = $request->PatientID;
        if ($request->filled('PatientBirthDate'))
            $patientReplace['PatientBirthDate'] = str_replace('-', '', $request->PatientBirthDate);
        if ($request->filled('PatientSex'))
            $patientReplace['PatientSex'] = $request->PatientSex;

        // Study Level
        if ($request->filled('StudyDescription'))
            $studyReplace['StudyDescription'] = $request->StudyDescription;
        if ($request->filled('AccessionNumber'))
            $studyReplace['AccessionNumber'] = $request->AccessionNumber;
        if ($request->filled('StudyDate'))
            $studyReplace['StudyDate'] = str_replace('-', '', $request->StudyDate);
        if ($request->filled('StudyTime'))
            $studyReplace['StudyTime'] = str_replace(':', '', $request->StudyTime);
        if ($request->filled('ReferringPhysicianName'))
            $studyReplace['ReferringPhysicianName'] = $request->ReferringPhysicianName;
        if ($request->filled('InstitutionName'))
            $studyReplace['InstitutionName'] = $request->InstitutionName;
        if ($request->filled('StudyID'))
            $studyReplace['StudyID'] = $request->StudyID;

        if (empty($patientReplace) && empty($studyReplace)) {
            return redirect()->route('pacs.study-detail', $id)->with('error', 'Tidak ada perubahan');
        }

        $successMsgs = [];
        $errorMsgs = [];
        $newStudyId = $id;

        // Lakukan modifikasi dalam satu request (Orthanc level) agar KeepSource=false tidak men-_cascade delete_ jika dipisah
        $replaceTags = array_merge($patientReplace, $studyReplace);

        $studyResult = $this->PACS->modifyStudy($id, $replaceTags, false);

        if ($studyResult['success']) {
            $newStudyId = $studyResult['data']['ID'] ?? $id;
            $successMsgs[] = "Data/Tag Pasien dan Studi berhasil diperbarui bersamaan.";
            AuditService::log('UPDATE', 'PACSStudy', $id, null, null, "Study tags modified. Old ID: {$id}, New ID: {$newStudyId}");
        } else {
            $errorMsgs[] = "Gagal memperbarui data: " . ($studyResult['body'] ?? 'Unknown API Error');
        }

        // Wait for PACS to index the new resource if Study ID changed
        if ($newStudyId !== $id) {
            $attempts = 0;
            $foundId = null;
            while ($attempts < 8 && !$foundId) {
                sleep(1);
                $check = $this->PACS->getStudy($newStudyId);
                if ($check) {
                    $foundId = $newStudyId;
                } else if ($attempts > 2) {
                    $checkOld = $this->PACS->getStudy($id);
                    if ($checkOld)
                        $foundId = $id;
                }
                $attempts++;
            }
            if ($foundId)
                $newStudyId = $foundId;
        }

        if (empty($errorMsgs)) {
            return redirect()->route('pacs.study-detail', $newStudyId)
                ->with('success', implode(" ", $successMsgs));
        } elseif (empty($successMsgs)) {
            return redirect()->route('pacs.study-detail', $id)
                ->with('error', implode(" ", $errorMsgs));
        } else {
            // Partial success
            return redirect()->route('pacs.study-detail', $newStudyId)
                ->with('warning', implode(" ", $successMsgs) . " Namun terdapat error: " . implode(" ", $errorMsgs));
        }
    }

    // ============================
    // SERIES DETAIL
    // ============================

    public function seriesDetail(string $id)
    {
        $seriesData = $this->PACS->getSeries($id);
        if (!$seriesData)
            return redirect()->route('pacs.studies')->with('error', 'Series tidak ditemukan');

        $study = $this->PACS->getStudy($seriesData['ParentStudy'] ?? '');
        $instances = [];
        foreach ($seriesData['Instances'] ?? [] as $instId) {
            $inst = $this->PACS->getInstance($instId);
            if ($inst) {
                $instances[] = $inst;
            }
        }

        // Sort instances by InstanceNumber
        usort($instances, function ($a, $b) {
            $numA = (int) ($a['MainDicomTags']['InstanceNumber'] ?? 0);
            $numB = (int) ($b['MainDicomTags']['InstanceNumber'] ?? 0);
            return $numA <=> $numB;
        });

        $baseUrl = $this->PACS->getPublicUrl();

        return view('pacs.series-detail', compact('seriesData', 'study', 'instances', 'baseUrl'));
    }

    // ============================
    // IMAGE PROXY (for inline preview)
    // ============================

    public function instancePreview(string $id)
    {
        $imageData = $this->PACS->getRaw("/instances/{$id}/preview");
        if (!$imageData)
            abort(404);

        return Response::make($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // ============================
    // VIEWERS
    // ============================

    public function viewer(string $id)
    {
        $study = $this->PACS->getStudy($id);
        if (!$study)
            return redirect()->route('pacs.studies')->with('error', 'Study tidak ditemukan');

        $baseUrl = $this->PACS->getPublicUrl();
        $studyUID = $study['MainDicomTags']['StudyInstanceUID'] ?? '';

        $viewers = [
            'explorer' => $this->PACS->getExplorerUrl($id),
            'ohif' => $this->PACS->getOHIFViewerUrl($studyUID),
            'osimis' => $this->PACS->getOsimisViewerUrl($id),
            'stone' => $this->PACS->getStoneViewerUrl($id),
        ];

        return view('pacs.viewer', compact('study', 'viewers', 'baseUrl', 'studyUID'));
    }

    // ============================
    // SEARCH
    // ============================

    public function search(Request $request)
    {
        $query = [];
        $searched = false;

        if ($request->filled('patient_name')) {
            $query['PatientName'] = '*' . $request->patient_name . '*';
            $searched = true;
        }
        if ($request->filled('patient_id')) {
            $query['PatientID'] = '*' . $request->patient_id . '*';
            $searched = true;
        }
        if ($request->filled('study_date_from') && $request->filled('study_date_to')) {
            $from = str_replace('-', '', $request->study_date_from);
            $to = str_replace('-', '', $request->study_date_to);
            $query['StudyDate'] = "{$from}-{$to}";
            $searched = true;
        } elseif ($request->filled('study_date_from')) {
            $query['StudyDate'] = str_replace('-', '', $request->study_date_from) . '-';
            $searched = true;
        } elseif ($request->filled('study_date_to')) {
            $query['StudyDate'] = '-' . str_replace('-', '', $request->study_date_to);
            $searched = true;
        }
        if ($request->filled('modality')) {
            $query['ModalitiesInStudy'] = $request->modality;
            $searched = true;
        }
        if ($request->filled('accession')) {
            $query['AccessionNumber'] = '*' . $request->accession . '*';
            $searched = true;
        }
        if ($request->filled('description')) {
            $query['StudyDescription'] = '*' . $request->description . '*';
            $searched = true;
        }

        $studies = [];
        if ($searched) {
            if (empty($query))
                $query['PatientName'] = '*';
            $studies = $this->PACS->findStudies($query) ?? [];
        }

        $baseUrl = $this->PACS->getPublicUrl();

        return view('pacs.search', compact('studies', 'searched', 'baseUrl'));
    }

    // ============================
    // UPLOAD IMAGE -> DICOM
    // ============================

    public function upload(Request $request)
    {
        $orderInfo = null;

        if ($request->filled('accession')) {
            $accession = $request->accession;

            // === Layer 1: Cek di database RIS lokal ===
            $order = \App\Models\RadiologyOrder::where('accession_number', $accession)->first();

            if ($order) {
                $orderInfo = [
                    'PatientID'       => $order->patient->no_rm ?? '',
                    'PatientName'     => $order->patient->nama ?? '',
                    'PatientSex'      => $order->patient->jenis_kelamin ?? '',
                    'PatientBirthDate'=> $order->patient->tgl_lahir
                                            ? date('Ymd', strtotime($order->patient->tgl_lahir))
                                            : '',
                    'AccessionNumber' => $order->accession_number,
                    'StudyDescription'=> $order->examinationType->name ?? '',
                    'Modality'        => $order->modality ?? 'OT',
                    '_source'         => 'RIS',
                ];
            } else {
                // === Layer 2: Fallback ke database SIMRS ===
                try {
                    $simrsOrder = \Illuminate\Support\Facades\DB::connection('simrs')
                        ->table('permintaan_radiologi')
                        ->join('reg_periksa', 'permintaan_radiologi.no_rawat', '=', 'reg_periksa.no_rawat')
                        ->join('pasien', 'reg_periksa.no_rkm_medis', '=', 'pasien.no_rkm_medis')
                        ->where('permintaan_radiologi.noorder', $accession)
                        ->select(
                            'pasien.no_rkm_medis',
                            'pasien.nm_pasien',
                            'pasien.tgl_lahir',
                            'pasien.jk',
                            'permintaan_radiologi.noorder',
                            'permintaan_radiologi.tgl_permintaan',
                            'permintaan_radiologi.diagnosa_klinis',
                            'permintaan_radiologi.informasi_tambahan'
                        )
                        ->first();

                    if ($simrsOrder) {
                        // Fetch examination items
                        $simrsItems = \Illuminate\Support\Facades\DB::connection('simrs')
                            ->table('permintaan_pemeriksaan_radiologi')
                            ->join('jns_perawatan_radiologi', 'permintaan_pemeriksaan_radiologi.kd_jenis_prw', '=', 'jns_perawatan_radiologi.kd_jenis_prw')
                            ->where('permintaan_pemeriksaan_radiologi.noorder', $accession)
                            ->select('jns_perawatan_radiologi.nm_perawatan', 'jns_perawatan_radiologi.kd_jenis_prw')
                            ->get();

                        $orderInfo = [
                            'PatientID'        => $simrsOrder->no_rkm_medis,
                            'PatientName'      => $simrsOrder->nm_pasien,
                            'PatientSex'       => $simrsOrder->jk,
                            'PatientBirthDate' => $simrsOrder->tgl_lahir
                                                    ? date('Ymd', strtotime($simrsOrder->tgl_lahir))
                                                    : '',
                            'AccessionNumber'  => $simrsOrder->noorder,
                            'StudyDescription' => $simrsItems->isNotEmpty()
                                                    ? $simrsItems->first()->nm_perawatan
                                                    : 'Pemeriksaan Radiologi',
                            'Modality'         => 'OT',
                            'TglPermintaan'    => $simrsOrder->tgl_permintaan,
                            'DiagnosaKlinis'   => $simrsOrder->diagnosa_klinis,
                            'InfoTambahan'     => $simrsOrder->informasi_tambahan,
                            'Items'            => json_decode(json_encode($simrsItems), true),
                            '_source'          => 'SIMRS',
                        ];
                    }
                } catch (\Exception $e) {
                    // SIMRS tidak tersedia, lanjut dengan form kosong
                    \Illuminate\Support\Facades\Log::warning('PACS upload: SIMRS fallback failed', ['error' => $e->getMessage()]);
                }
            }
        }

        return view('pacs.upload', compact('orderInfo'));
    }

    public function storeUpload(Request $request)
    {
        $request->validate([
            'PatientID' => 'required|string',
            'PatientName' => 'required|string',
            'AccessionNumber' => 'nullable|string',
            'StudyDescription' => 'nullable|string',
            'Modality' => 'nullable|string|max:2',
            'dicom_images' => 'required|array',
            'dicom_images.*' => 'image|mimes:jpeg,png,jpg|max:5120', // Max 5MB per image
        ]);

        $tags = [
            'PatientID' => $request->PatientID,
            'PatientName' => strtoupper($request->PatientName),
            'PatientSex' => $request->PatientSex ?? 'O',
            'PatientBirthDate' => str_replace('-', '', $request->PatientBirthDate ?? ''),
            'AccessionNumber' => $request->AccessionNumber ?? '',
            'StudyDescription' => $request->StudyDescription ?? 'MANUAL UPLOAD',
            'Modality' => $request->Modality ?? 'OT',
            'SeriesDescription' => 'Uploaded Images',
            'StudyDate' => date('Ymd'),
            'StudyTime' => date('His')
        ];

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($request->file('dicom_images') as $file) {
            try {
                // Konversi gambar apapun (termasuk PNG) menjadi format standar JPEG
                // Karena Orthanc /tools/create-dicom hanya mendukung ekstraksi piksel dari format JPEG Baseline.
                $imagePath = $file->getRealPath();
                $imageInfo = getimagesize($imagePath);
                $mime = $imageInfo['mime'];

                if ($mime == 'image/png') {
                    $img = imagecreatefrompng($imagePath);
                    $bg = imagecreatetruecolor(imagesx($img), imagesy($img));
                    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
                    imagealphablending($bg, TRUE);
                    imagecopy($bg, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
                    imagedestroy($img);
                    $img = $bg;
                } elseif ($mime == 'image/jpeg' || $mime == 'image/jpg') {
                    $img = imagecreatefromjpeg($imagePath);
                } else {
                    $img = null;
                }

                if ($img) {
                    ob_start();
                    imagejpeg($img, null, 100); # Kualitas 100% JPEG
                    $imageData = ob_get_clean();
                    imagedestroy($img);
                    $imageBase64 = base64_encode($imageData);
                } else {
                    // Fallback jika gd tidak mempan
                    $imageBase64 = base64_encode(file_get_contents($imagePath));
                }

                // Send payload data directly to the new Orthanc Python Native Plugin /api/ris-upload-image
                $payload = array_merge($tags, [
                    'ImageBase64' => $imageBase64
                ]);

                $result = $this->PACS->post('/api/ris-upload-image', $payload);

                if ($result['success'] && isset($result['data']['success']) && $result['data']['success']) {
                    $successCount++;
                    // Beri sedikit jeda agar SQLite Orthanc tidak lock/busy jika upload banyak sekaligus
                    usleep(200000);
                } else {
                    $errorCount++;
                    $errMsg = $result['body'] ?? 'Unknown API error';
                    $errors[] = $errMsg;
                    \Illuminate\Support\Facades\Log::error('PACS img-to-DICOM error', ['error' => $errMsg]);
                }
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = $e->getMessage();
                \Illuminate\Support\Facades\Log::error('PACS img-to-DICOM exception', ['error' => $e->getMessage()]);
            }
        }

        if ($successCount > 0 && $errorCount == 0) {
            return redirect()->route('pacs.studies')->with('success', "Berhasil mengunggah dan konversi {$successCount} gambar JPG/PNG  ke DICOM PACS.");
        } elseif ($successCount > 0) {
            return redirect()->route('pacs.studies')->with('warning', "Berhasil: {$successCount}, Gagal: {$errorCount}. " . implode('. ', array_slice($errors, 0, 2)));
        } else {
            $errDetail = count($errors) > 0 ? ' Detail: ' . implode('. ', array_slice($errors, 0, 2)) : '';
            return back()->withInput()->with('error', 'Gagal mengunggah semua gambar ke PACS.' . $errDetail);
        }
    }

    // ============================
    // MODALITY MANAGEMENT (PACS SIDE)
    // ============================

    public function modalities()
    {
        $modalityNames = $this->PACS->getModalities() ?? [];
        $modalities = [];

        foreach ($modalityNames as $name) {
            $details = $this->PACS->getModalityDetails($name);
            $config = $this->PACS->getModalityConfiguration($name);

            if ($details) {
                // Normalisasi: Orthanc bisa mengembalikan array ["AET", "Host", Port] untuk Simple modality
                if (isset($details[0])) {
                    $details = [
                        'AET' => $details[0],
                        'Host' => $details[1] ?? '',
                        'Port' => $details[2] ?? 104,
                    ];
                }

                // Merge details and configuration (config might contain things details doesn't or vice versa)
                $merged = array_merge($details, $config ?? []);
                $merged['Name'] = $name;
                $modalities[] = $merged;
            }
        }

        return view('pacs.modalities', compact('modalities'));
    }

    public function storeModality(Request $request)
    {
        $request->validate([
            'Name' => 'required|string|max:64',
            'AET' => 'required|string|max:16', // DICOM standard
            'Host' => 'required|string|max:255',
            'Port' => 'required|integer|min:1|max:65535',
            'Manufacturer' => 'nullable|string',
            'AllowEcho' => 'nullable|boolean',
            'AllowFind' => 'nullable|boolean',
            'AllowGet' => 'nullable|boolean',
            'AllowMove' => 'nullable|boolean',
            'AllowStore' => 'nullable|boolean',
        ]);

        $name = trim($request->Name);

        // Standard DICOM permissions - Orthanc requires standard C-xxx flags to be boolean
        $payload = [
            'AET' => trim($request->AET),
            'Host' => trim($request->Host),
            'Port' => (int) $request->Port,
            'AllowEcho' => $request->boolean('AllowEcho', true),
            'AllowFind' => $request->boolean('AllowFind', true),
            'AllowMove' => $request->boolean('AllowMove', true),
            'AllowStore' => $request->boolean('AllowStore', true),
        ];

        if ($request->filled('Manufacturer')) {
            $payload['Manufacturer'] = trim($request->Manufacturer);
        }

        // Log payload before sending
        \Log::debug("PACS MODALITY ATTEMPT for {$name}:", $payload);

        // Use rawurlencode for safety in path & use OrthancService for persistent save
        $result = $this->orthanc->saveModality(rawurlencode($name), $payload);

        if ($result['success']) {
            AuditService::log('UPDATE', 'PACSModality', $name, null, $payload, "Modality updated/created in PACS: {$name}");
            return redirect()->back()->with('success', "Konfigurasi modalitas '{$name}' berhasil disimpan.");
        }

        \Log::error('Modalities Update Error:', [
            'name' => $name,
            'payload' => $payload,
            'response' => $result
        ]);

        $errorMsg = is_string($result['body']) ? $result['body'] : 'Unknown Error';
        return redirect()->back()->withInput()->with('error', "Gagal menyimpan modalitas: " . $errorMsg);
    }

    public function destroyModality(string $name)
    {
        $result = $this->PACS->deleteModalityDevice($name);

        if ($result['success']) {
            AuditService::log('DELETE', 'PACSModality', $name, null, null, "Modality deleted from PACS: {$name}");
            return redirect()->back()->with('success', "Modalitas '{$name}' berhasil dihapus dari PACS.");
        }

        return redirect()->back()->with('error', "Gagal menghapus modalitas.");
    }

    public function sendToModality(Request $request)
    {
        try {
            // Get data from request (support list or single)
            $modality = $request->modality;
            $id = $request->study_id ?? $request->id ?? (is_array($request->study_ids) ? $request->study_ids[0] : null);

            if (!$modality || !$id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modality dan Study ID wajib diisi.'
                ], 422);
            }

            // Increase execution time for potentially slow DICOM transfer
            set_time_limit(600); 

            // Orthanc API: POST /modalities/{modality}/store
            $uri = "/modalities/" . rawurlencode($modality) . "/store";
            
            // Send study Orthanc ID with 600 seconds timeout
            $result = $this->PACS->post($uri, $id, 600);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true, 
                    'message' => "Study berhasil dikirim ke modality '{$modality}'."
                ]);
            }

            return response()->json([
                'success' => false, 
                'message' => "Gagal mengirim: " . ($result['body'] ?? 'Unknown Error')
            ], 400);

        } catch (\Exception $e) {
            \Log::error('PACS sendToModality Exception: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}


