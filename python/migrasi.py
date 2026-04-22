import requests
from requests.auth import HTTPBasicAuth
import sys

# ==============================
# KONFIGURASI
# ==============================
# sumber
SOURCE_URL = "http://192.168.3.14:8042"
SOURCE_USERNAME = "orthanc"
SOURCE_PASSWORD = "orthanc"

# target
DEST_URL = "http://localhost:8042"
DEST_USERNAME = "orthanc"
DEST_PASSWORD = "orthanc"

MAX_STUDIES = 100

# FILTER TANGGAL (18–20 April 2026)
DATE_RANGE = "20260418-20260420"

# ==============================
# AUTH
# ==============================
source_auth = HTTPBasicAuth(SOURCE_USERNAME, SOURCE_PASSWORD)
dest_auth = HTTPBasicAuth(DEST_USERNAME, DEST_PASSWORD)

# ==============================
# AMBIL STUDIES BERDASARKAN TANGGAL
# ==============================
query = {
    "Level": "Study",
    "Query": {
        "StudyDate": DATE_RANGE
    }
}

try:
    print(f"Menghubungi sumber dengan filter tanggal {DATE_RANGE} ...")
    response = requests.post(
        f"{SOURCE_URL}/tools/find",
        json=query,
        auth=source_auth
    )
    response.raise_for_status()
except Exception as e:
    print("Gagal query studies:", e)
    sys.exit(1)

studies = response.json()
total_found = len(studies)

print(f"Total studies ditemukan: {total_found}")

if total_found == 0:
    print("Tidak ada study dalam rentang tanggal tersebut.")
    sys.exit(0)

# Batasi jumlah
studies_to_copy = studies[:MAX_STUDIES]
print(f"Akan dicopy {len(studies_to_copy)} studies\n")

# ==============================
# COPY STUDIES
# ==============================
success = 0
failed = 0

for index, study_id in enumerate(studies_to_copy, start=1):
    print(f"[{index}/{len(studies_to_copy)}] Study: {study_id}")

    try:
        # Ambil instance dalam study
        res_instances = requests.get(
            f"{SOURCE_URL}/studies/{study_id}/instances",
            auth=source_auth
        )
        res_instances.raise_for_status()
        instances = res_instances.json()

        print(f"  -> {len(instances)} instance ditemukan")

        err_in_study = False

        for k, inst in enumerate(instances, start=1):
            inst_id = inst["ID"]

            try:
                # Download DICOM
                dicom_res = requests.get(
                    f"{SOURCE_URL}/instances/{inst_id}/file",
                    auth=source_auth
                )
                dicom_res.raise_for_status()

                # Upload ke tujuan
                upload_res = requests.post(
                    f"{DEST_URL}/instances",
                    data=dicom_res.content,
                    auth=dest_auth
                )

                if upload_res.status_code != 200:
                    print(f"     ✘ Upload gagal: {inst_id} ({upload_res.status_code})")
                    err_in_study = True

            except Exception as e:
                print(f"     ✘ Error instance {inst_id}: {e}")
                err_in_study = True

        if not err_in_study:
            print("   ✔ Berhasil")
            success += 1
        else:
            print("   ✘ Gagal sebagian")
            failed += 1

    except Exception as e:
        print(f"   ✘ Error study: {e}")
        failed += 1

print("\n===== SELESAI =====")
print(f"Berhasil : {success}")
print(f"Gagal    : {failed}")