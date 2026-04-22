import requests
from requests.auth import HTTPBasicAuth
import sys

# ==============================
# KONFIGURASI
# ==============================
SOURCE_URL = "http://192.168.3.14:8042"
SOURCE_USERNAME = "orthanc"          # Username sumber
SOURCE_PASSWORD = "orthanc"          # Password sumber

DEST_URL = "http://192.168.3.12:8042"   # URL orthanc tujuan
DEST_USERNAME = "orthanc"            # Username tujuan
DEST_PASSWORD = "orthanc"            # Password tujuan

MAX_STUDIES = 10

# ==============================
# AUTH
# ==============================
source_auth = HTTPBasicAuth(SOURCE_USERNAME, SOURCE_PASSWORD)
dest_auth = HTTPBasicAuth(DEST_USERNAME, DEST_PASSWORD)

# ==============================
# AMBIL STUDIES DARI SUMBER
# ==============================
try:
    print(f"Menghubungi sumber: {SOURCE_URL}...")
    response = requests.get(f"{SOURCE_URL}/studies", auth=source_auth)
    response.raise_for_status()
except Exception as e:
    print("Gagal mengambil daftar studies dari sumber:", e)
    sys.exit(1)

studies = response.json()
total_found = len(studies)
print(f"Total studies ditemukan di sumber: {total_found}")

if total_found == 0:
    print("Tidak ada study untuk dicopy.")
    sys.exit(0)

studies_to_copy = []
print("Memfilter studies dengan Accession Number...")
for study_id in studies:
    if len(studies_to_copy) >= MAX_STUDIES:
        break
    try:
        study_info = requests.get(f"{SOURCE_URL}/studies/{study_id}", auth=source_auth).json()
        main_dicom_tags = study_info.get("MainDicomTags", {})
        # AccessionNumber could be missing, empty string, or None
        accession = main_dicom_tags.get("AccessionNumber", "")
        if accession and str(accession).strip() != "":
            studies_to_copy.append(study_id)
            sys.stdout.write(f"\rDitemukan: {len(studies_to_copy)}/{MAX_STUDIES} studies dengan Accession Number...")
            sys.stdout.flush()
    except Exception as e:
        continue

print(f"\nSelesai filter. Akan dicopy {len(studies_to_copy)} studies ke localhost\n")

# ==============================
# COPY STUDIES (VIA EXPORT/IMPORT API)
# ==============================
success = 0
failed = 0

for index, study_id in enumerate(studies_to_copy, start=1):
    print(f"[{index}/{len(studies_to_copy)}] Memproses study: {study_id}")

    try:
        # Ambil daftar instances di study ini
        res_instances = requests.get(f"{SOURCE_URL}/studies/{study_id}/instances", auth=source_auth)
        res_instances.raise_for_status()
        instances = res_instances.json()
        
        print(f"  -> Ditemukan {len(instances)} instance/gambar didalam study. Download & upload...")
        
        err_in_study = False
        
        for k, inst in enumerate(instances, start=1):
            inst_id = inst["ID"]
            
            # Download file DICOM
            dicom_data = requests.get(f"{SOURCE_URL}/instances/{inst_id}/file", auth=source_auth).content
            
            # Upload file DICOM ke localhost
            upload_res = requests.post(f"{DEST_URL}/instances", data=dicom_data, auth=dest_auth)
            
            if upload_res.status_code != 200:
                print(f"     ✘ Gagal upload instance: {inst_id} - HTTP {upload_res.status_code}")
                err_in_study = True
                
        if not err_in_study:
            print("   ✔ Berhasil")
            success += 1
        else:
            print("   ✘ Gagal sebagian/seluruhnya")
            failed += 1

    except Exception as e:
        print(f"   ✘ Error pada study {study_id}: {e}")
        failed += 1

print("\n===== SELESAI =====")
print(f"Berhasil : {success} Studies")
print(f"Gagal    : {failed} Studies")