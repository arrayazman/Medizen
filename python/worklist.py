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
        wl_path = os.path.join(wl_dir, f"{base_name}.wl")

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

# ==============================================================================
# ENDPOINT: UPLOAD JPG/PNG KE DICOM (NATIVE ORTHANC)
# ==============================================================================
import base64

def create_dicom_from_image(output, uri, **request):
    """
    Endpoint POST /api/ris-upload-image
    Menerima Payload JSON berisi Body Gambar (Base64) beserta parameter tag pasien.
    Menggunakan API bawaan Orthanc /tools/create-dicom tanpa ketergantungan pydicom/numpy.
    """
    if request['method'] != 'POST':
        output.SendMethodNotAllowed('POST')
        return

    try:
        body = request['body']
        if type(body) == bytes:
            body = body.decode('utf-8')
            
        data = json.loads(body)

        image_base64 = data.get("ImageBase64")
        if not image_base64:
            output.SendHttpStatus(400, "ImageBase64 payload is required")
            return

        # Ambil Metadata tambahan
        patient_name = data.get('PatientName', 'Anonymous')
        patient_id = data.get('PatientID', '000000')
        patient_birth_date = data.get('PatientBirthDate', '')
        patient_sex = data.get('PatientSex', 'O')
        accession_number = data.get('AccessionNumber', '')
        study_description = data.get('StudyDescription', 'Manual Upload')
        modality = data.get('Modality', 'OT').upper()
        
        # Ekstrak data base64 sebenarnya jika terdapat header data:image/png;base64,...
        if "," in image_base64:
            image_base64 = image_base64.split(",")[1]

        # Menyiapkan payload untuk internal Orthanc tools
        payload = {
            "Force": True, # Wajib agar tag parent bisa ditimpa
            "Tags": {
                "SpecificCharacterSet": "ISO_IR 100",
                "PatientName": patient_name,
                "PatientID": patient_id,
                "PatientBirthDate": patient_birth_date,
                "PatientSex": patient_sex,
                "AccessionNumber": accession_number,
                "StudyDescription": study_description,
                "SeriesDescription": "Uploaded Images",
                "Modality": modality,
                "Manufacturer": "RIS MEDIZEN",
            },
            # Isi dari content gambar JPEG/PNG yang di-encode base64
            "Content": "data:image/jpeg;base64," + image_base64
        }

        # Request ke instansi Orthanc internal untuk memahat DICOM baru
        max_retries = 3
        instance_id = None
        for i in range(max_retries):
            try:
                response_json = orthanc.RestApiPost('/tools/create-dicom', json.dumps(payload))
                response_data = json.loads(response_json)
                instance_id = response_data.get("ID")
                if instance_id:
                    break
            except Exception as ex:
                if "SQLite: Cannot run a cached statement" in str(ex) and i < max_retries - 1:
                    orthanc.LogWarning(f"SQLite Busy (Attempt {i+1}), retrying in 0.5s...")
                    time.sleep(0.5)
                    continue
                raise ex

        if not instance_id:
            raise Exception("Gagal melakukan Generate Image To DICOM (ID empty)")

        orthanc.LogWarning(f"Berhasil mengkonversi dan menyimpan gambar JPG/PNG ke PACS ID: {instance_id}")
        output.AnswerBuffer(json.dumps({'success': True, 'ID': instance_id}), 'application/json')

    except Exception as e:
        orthanc.LogError(f"Error processing image upload: {str(e)}")
        output.SendHttpStatus(500, str(e))

orthanc.RegisterRestCallback('/api/ris-upload-image', create_dicom_from_image)
orthanc.LogWarning("RIS Upload API Native loaded: Registered POST /api/ris-upload-image")

