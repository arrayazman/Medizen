import requests
from requests.auth import HTTPBasicAuth
import sys

# ==============================
# KONFIGURASI
# ==============================
SOURCE_URL = "http://IP_SOURCE:8042"
SOURCE_USERNAME = "orthanc"          # Username sumber
SOURCE_PASSWORD = "orthanc"          # Password sumber

DEST_URL = "http://IP_DESTINATION:8042"   # URL orthanc tujuan
DEST_USERNAME = "orthanc"            # Username tujuan
DEST_PASSWORD = "orthanc"            # Password tujuan

MAX_STUDIES = 10000

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

studies_to_copy = studies[:MAX_STUDIES]
print(f"Akan dicopy maksimal {len(studies_to_copy)} studies ke localhost\n")

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