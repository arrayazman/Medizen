import orthanc
import json
import os
import time

# Konstanta Direktori Worklist Orthanc
# Ubah path ini sesuai dengan konfigurasi "Worklists" : { "Database" : "..." } di orthanc.json Anda.
WORKLIST_DIR = '/var/lib/orthanc/worklists'

def create_worklist(output, uri, **request):
    """
    Endpoint POST /worklists
    Menerima JSON dari sistem RIS (Laravel) dan membuat file .wl untuk dibaca oleh modalitas.
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
        
        # Accession Number sebagai nama file (wajib ada untuk menghindari duplikasi)
        accession_number = data.get('AccessionNumber')
        if not accession_number:
            output.SendHttpStatus(400, "AccessionNumber is required in payload")
            return

        # Pastikan direktori worklist ada
        if not os.path.exists(WORKLIST_DIR):
            os.makedirs(WORKLIST_DIR)

        # Buat file sementara (dump2dcm syntax)
        # Format ini menggunakan sintaks dcmtk standar untuk membuat file DICOM
        base_name = f"{accession_number}_{int(time.time())}"
        txt_path = os.path.join(WORKLIST_DIR, f"{base_name}.txt")
        wl_path = os.path.join(WORKLIST_DIR, f"{base_name}.wl")

        # Tulis data DICOM ke dalam format teks yang bisa dikonversi oleh dump2dcm
        with open(txt_path, 'w') as f:
            # Format karakter set (ISO_IR 100)
            f.write('(0008,0005) CS [ISO_IR 100] # 10, 1 SpecificCharacterSet\n')
            
            # Data Pasien
            f.write(f'(0010,0010) PN [{data.get("PatientName", "")}] # PatientName\n')
            f.write(f'(0010,0020) LO [{data.get("PatientID", "")}] # PatientID\n')
            f.write(f'(0010,0030) DA [{data.get("PatientBirthDate", "")}] # PatientBirthDate\n')
            f.write(f'(0010,0040) CS [{data.get("PatientSex", "")}] # PatientSex\n')
            
            # Data Study / Order
            f.write(f'(0008,0050) SH [{data.get("AccessionNumber", "")}] # AccessionNumber\n')
            f.write(f'(0008,0090) PN [{data.get("ReferringPhysicianName", "")}] # ReferringPhysicianName\n')
            f.write(f'(0032,1060) LO [{data.get("RequestedProcedureDescription", "")}] # RequestedProcedureDescription\n')
            f.write(f'(0020,000d) UI [{data.get("StudyInstanceUID", "")}] # StudyInstanceUID\n')
            
            # Requested Procedure Step Sequence (Item)
            f.write('(0040,0100) SQ (Sequence with undefined length) # ScheduledProcedureStepSequence\n')
            f.write('  (fffe,e000) na (Item with undefined length) # Item\n')
            f.write(f'    (0008,0060) CS [{data.get("Modality", "")}] # Modality\n')
            f.write(f'    (0040,0001) AE [{data.get("ScheduledStationAETitle", "")}] # ScheduledStationAETitle\n')
            f.write(f'    (0040,0002) DA [{data.get("ScheduledProcedureStepStartDate", "")}] # ScheduledProcedureStepStartDate\n')
            f.write(f'    (0040,0003) TM [{data.get("ScheduledProcedureStepStartTime", "")}] # ScheduledProcedureStepStartTime\n')
            f.write(f'    (0040,0006) PN [{data.get("ScheduledPerformingPhysicianName", "")}] # ScheduledPerformingPhysicianName\n')
            f.write('  (fffe,e00d) na (ItemDelimitationItem for re-encoding) # ItemDelimitationItem\n')
            f.write('(fffe,e0dd) na (SequenceDelimitationItem for re-encoding) # SequenceDelimitationItem\n')

        # Konversi TXT ke WL menggunakan tool dump2dcm bawaan Orthanc/DCMTK
        import subprocess
        # Pastikan dump2dcm ada di PATH server Anda
        result = subprocess.run(['dump2dcm', txt_path, wl_path], capture_output=True, text=True)

        if result.returncode != 0:
            orthanc.LogError(f"Failed to generate worklist file: {result.stderr}")
            output.SendHttpStatus(500, f"Error generating .wl file: {result.stderr}")
            return
            
        # Hapus file teks sementara
        if os.path.exists(txt_path):
            os.remove(txt_path)

        orthanc.LogWarning(f"Worklist created successfully: {wl_path}")
        output.AnswerBuffer(json.dumps({'success': True, 'file': wl_path}), 'application/json')

    except Exception as e:
        orthanc.LogError(f"Error processing worklist: {str(e)}")
        output.SendHttpStatus(500, str(e))

# Daftarkan endpoint ke Orthanc
orthanc.RegisterRestCallback('/worklists', create_worklist)
orthanc.LogWarning("Worklist API plugin loaded: Registered POST /worklists")
