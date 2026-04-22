import orthanc
import json
import os
import time

def create_worklist(output, uri, **request):
    """
    Endpoint POST /api/ris-worklist
    Menerima JSON dari sistem RIS (Laravel) dan membuat file .wl untuk dibaca oleh modalitas.
    Menggunakan API bawaan Orthanc tanpa ketergantungan dcmtk (dump2dcm).
    """
    if request['method'] != 'POST':
        output.SendMethodNotAllowed('POST')
        return

    try:
        # Parsing payload JSON dari RIS
        body = request['body']
        if type(body) == bytes:
            body = body.decode('utf-8')
        
        data = json.loads(body)
        
        # Accession Number sebagai nama file
        accession_number = data.get('AccessionNumber')
        if not accession_number:
            output.SendHttpStatus(400, "AccessionNumber is required in payload")
            return

        # Otomatis mengambil direktori Worklist dari konfigurasi Orthanc
        config_str = orthanc.GetConfiguration()
        config = json.loads(config_str)
        worklists_config = config.get("Worklists", {})
        wl_dir = worklists_config.get("Database", "")
        
        # Jika tidak diatur, fallback ke default path /var/lib/orthanc/worklists atau relatif folder
        if not wl_dir:
            if os.name == 'nt': # Windows
                wl_dir = "Worklists"
            else:
                wl_dir = "/var/lib/orthanc/worklists"

        # Pastikan direktori worklist ada
        if not os.path.exists(wl_dir):
            try:
                os.makedirs(wl_dir)
            except Exception as e:
                orthanc.LogWarning(f"Cannot create {wl_dir}: {e}")

        # Buat file sementara
        base_name = f"{accession_number}_{int(time.time())}"
        # wl_path = os.path.join(wl_dir, f"{base_name}.wl")
        wl_path = os.path.join(wl_dir, f"{accession_number}.wl")

        # Buat konten DICOM Tag Structure untuk Worklist
        payload = {
            "Force": True, # Wajib agar tag parent (StudyInstanceUID dll) bisa dioverride
            "Tags": {
                "SpecificCharacterSet": "ISO_IR 100",
                "PatientName": data.get("PatientName", ""),
                "PatientID": data.get("PatientID", ""),
                "PatientBirthDate": data.get("PatientBirthDate", ""),
                "PatientSex": data.get("PatientSex", ""),
                "AccessionNumber": data.get("AccessionNumber", ""),
                "ReferringPhysicianName": data.get("ReferringPhysicianName", ""),
                "RequestedProcedureDescription": data.get("RequestedProcedureDescription", ""),
                "StudyInstanceUID": data.get("StudyInstanceUID", ""),
                "ScheduledProcedureStepSequence": [
                    {
                        "Modality": data.get("Modality", ""),
                        "ScheduledStationAETitle": data.get("ScheduledStationAETitle", ""),
                        "ScheduledProcedureStepStartDate": data.get("ScheduledProcedureStepStartDate", ""),
                        "ScheduledProcedureStepStartTime": data.get("ScheduledProcedureStepStartTime", ""),
                        "ScheduledPerformingPhysicianName": data.get("ScheduledPerformingPhysicianName", "")
                    }
                ]
            }
        }

        # Request ke instansi Orthanc internal untuk membuat DICOM secara native dari tags
        response_json = orthanc.RestApiPost('/tools/create-dicom', json.dumps(payload))
        response_data = json.loads(response_json)
        
        instance_id = response_data.get("ID")
        if not instance_id:
            raise Exception("Gagal membuat instance DICOM dari payload")

        # Ambil RAW byte DICOM file yang baru dibuat
        dicom_bytes = orthanc.RestApiGet(f'/instances/{instance_id}/file')

        # Tulis RAW byte hasil generated orthanc ke ekstensi file .wl
        with open(wl_path, 'wb') as f:
            if type(dicom_bytes) == str:
                f.write(dicom_bytes.encode('latin1')) # Backward compatibility if Orthanc returns string
            else:
                f.write(dicom_bytes)

        # Hapus temporary instance dari database Orthanc agar tidak menumpuk
        orthanc.RestApiDelete(f'/instances/{instance_id}')

        orthanc.LogWarning(f"Worklist created successfully natively: {wl_path}")
        output.AnswerBuffer(json.dumps({'success': True, 'file': wl_path}), 'application/json')

    except Exception as e:
        orthanc.LogError(f"Error processing worklist natively: {str(e)}")
        output.SendHttpStatus(500, str(e))

# Daftarkan endpoint ke Orthanc
orthanc.RegisterRestCallback('/api/ris-worklist', create_worklist)
orthanc.LogWarning("Worklist API Native plugin loaded: Registered POST /api/ris-worklist")
