import requests
from requests.auth import HTTPBasicAuth
import sys

# ==============================
# KONFIGURASI
# ==============================
SOURCE_URL = "http://IP_SOURCE:8042"
SOURCE_USERNAME = "orthanc"
SOURCE_PASSWORD = "orthanc"

DEST_URL = "http://IP_DESTINATION:8042"
DEST_USERNAME = "orthanc"
DEST_PASSWORD = "orthanc"

MAX_STUDIES = 10000

# ==============================
# AUTH
# ==============================
source_auth = HTTPBasicAuth(SOURCE_USERNAME, SOURCE_PASSWORD)
dest_auth = HTTPBasicAuth(DEST_USERNAME, DEST_PASSWORD)

# ==============================
# CARI STUDIES (SUPER CEPAT)
# ==============================
print("Mencari studies dengan AccessionNumber prefix 'PR'...")

query = {
    "Level": "Study",
    "Query": {
        "AccessionNumber": "PR*"
    },
    "Limit": MAX_STUDIES,
    "Expand": False   # 🔥 hasil pasti list string ID
}

try:
    response = requests.post(
        f"{SOURCE_URL}/tools/find",
        json=query,
        auth=source_auth,
        timeout=60
    )

    if response.status_code != 200:
        print("HTTP ERROR:", response.status_code)
        print(response.text)
        sys.exit(1)

    try:
        results = response.json()
    except Exception:
        print("Response bukan JSON:")
        print(response.text)
        sys.exit(1)

    if not isinstance(results, list):
        print("Response bukan list!")
        print(results)
        sys.exit(1)

except Exception as e:
    print("Gagal query ke Orthanc:", e)
    sys.exit(1)

print(f"Ditemukan {len(results)} studies dengan prefix PR")

if len(results) == 0:
    print("Tidak ada data untuk dicopy.")
    sys.exit(0)

# ==============================
# AMBIL ID STUDY (SUPPORT SEMUA FORMAT)
# ==============================
studies_to_copy = []

for r in results:
    if isinstance(r, dict):
        if "ID" in r:
            studies_to_copy.append(r["ID"])
    elif isinstance(r, str):
        studies_to_copy.append(r)

if len(studies_to_copy) == 0:
    print("Tidak ada ID valid ditemukan.")
    print("DEBUG SAMPLE:", results[:3])
    sys.exit(0)

# ==============================
# COPY STUDIES
# ==============================
success = 0
failed = 0

for index, study_id in enumerate(studies_to_copy, start=1):
    print(f"[{index}/{len(studies_to_copy)}] Memproses study: {study_id}")

    try:
        # ambil instance
        res_instances = requests.get(
            f"{SOURCE_URL}/studies/{study_id}/instances",
            auth=source_auth,
            timeout=30
        )

        if res_instances.status_code != 200:
            print("   ✘ Gagal ambil instance")
            failed += 1
            continue

        instances = res_instances.json()
        print(f"   -> {len(instances)} instance ditemukan")

        error_in_study = False

        for inst in instances:
            inst_id = inst.get("ID")
            if not inst_id:
                continue

            try:
                # download dicom
                dicom_res = requests.get(
                    f"{SOURCE_URL}/instances/{inst_id}/file",
                    auth=source_auth,
                    timeout=30
                )

                if dicom_res.status_code != 200:
                    print(f"     ✘ Gagal download {inst_id}")
                    error_in_study = True
                    continue

                # upload ke tujuan
                upload_res = requests.post(
                    f"{DEST_URL}/instances",
                    data=dicom_res.content,
                    auth=dest_auth,
                    timeout=30
                )

                if upload_res.status_code != 200:
                    print(f"     ✘ Gagal upload {inst_id}")
                    error_in_study = True

            except Exception as e:
                print(f"     ✘ Error instance: {e}")
                error_in_study = True

        if not error_in_study:
            print("   ✔ Berhasil")
            success += 1
        else:
            print("   ✘ Gagal sebagian")
            failed += 1

    except Exception as e:
        print(f"   ✘ Error study: {e}")
        failed += 1

# ==============================
# HASIL
# ==============================
print("\n===== SELESAI =====")
print(f"Berhasil : {success} Studies")
print(f"Gagal    : {failed} Studies")