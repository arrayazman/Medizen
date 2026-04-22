import mysql.connector
import requests
import time
from datetime import datetime, timedelta
import os
import sys
import hashlib

# =========================
# LOGGING
# =========================
def log(msg, level="INFO"):
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    label = {
        "INFO":    "INFO ",
        "OK":      "OK   ",
        "FAIL":    "FAIL ",
        "ERROR":   "ERROR",
        "SKIP":    "SKIP ",
        "WARN":    "WARN ",
    }.get(level, level)
    print(f"[{timestamp}] [{label}] {msg}")
    sys.stdout.flush()

def log_table(rows):
    """Tampilkan data terkirim dalam bentuk tabel."""
    if not rows:
        return

    headers = [
        "Kode Permintaan",
        "Nama Pasien",
        "Pemeriksaan",
        "Info Tambahan",
        "Indikasi",
        "Dokter Pengirim",
    ]

    col_widths = [len(h) for h in headers]
    for row in rows:
        for i, val in enumerate(row):
            col_widths[i] = max(col_widths[i], len(str(val)))

    sep = "+-" + "-+-".join("-" * w for w in col_widths) + "-+"
    header_line = "| " + " | ".join(h.ljust(col_widths[i]) for i, h in enumerate(headers)) + " |"

    print()
    print(sep)
    print(header_line)
    print(sep)
    for row in rows:
        line = "| " + " | ".join(str(val).ljust(col_widths[i]) for i, val in enumerate(row)) + " |"
        print(line)
    print(sep)
    print()
    sys.stdout.flush()


# =========================
# CONFIG
# =========================
DB_CONFIG = {
    'host':     os.getenv('DB_HOST',  'localhost'),
    'user':     os.getenv('DB_USER',  'root'),
    'password': os.getenv('DB_PASS',  ''),
    'database': os.getenv('DB_NAME',  'sik'),
}

ORTHANC_URL  = os.getenv('ORTHANC_URL',  "http://localhost:8042")
ORTHANC_USER = os.getenv('ORTHANC_USER', "orthanc")
ORTHANC_PASS = os.getenv('ORTHANC_PASS', "orthanc")

INTERVAL = int(os.getenv("INTERVAL", "20"))

MODALITY_MAPPING = {
    'R01': 'CR',
    'R02': 'US',
    'R03': 'CT',
    'R04': 'MR',
}

# =========================
# CACHE LOKAL
# Menyimpan noorder yang sudah berhasil dikirim ke Orthanc.
# Persisten selama service berjalan (in-memory).
# Direset otomatis tiap hari baru agar data hari ini tidak
# tertahan oleh cache hari sebelumnya.
# =========================
SENT_CACHE: set = set()
CACHE_DATE: str = datetime.now().strftime("%Y-%m-%d")

def reset_cache_if_new_day():
    global SENT_CACHE, CACHE_DATE
    today = datetime.now().strftime("%Y-%m-%d")
    if today != CACHE_DATE:
        log(f"Hari baru ({today}), cache direset.", "INFO")
        SENT_CACHE = set()
        CACHE_DATE = today

def mark_sent(noorder: str):
    SENT_CACHE.add(noorder)

def already_sent(noorder: str) -> bool:
    return noorder in SENT_CACHE


# =========================
# GENERATE UID DICOM VALID
# =========================
def generate_uid_from_str(text):
    """
    UID deterministik dari noorder.
    Selalu menghasilkan UID yang sama untuk noorder yang sama,
    sehingga tidak konflik saat order dikirim ulang antar siklus.
    """
    h = int(hashlib.md5(text.encode()).hexdigest(), 16) % (10 ** 38)
    return f"2.25.{h}"


# =========================
# FORMATTER
# =========================
def format_date(d):
    try:
        if not d:
            return ""
        if isinstance(d, str):
            return d.replace("-", "")
        return d.strftime("%Y%m%d")
    except:
        return ""

def format_time(t):
    try:
        if not t:
            return "000000"
        if isinstance(t, str):
            return t.replace(":", "")
        if isinstance(t, timedelta):
            total_seconds = int(t.total_seconds())
            h = total_seconds // 3600
            m = (total_seconds % 3600) // 60
            s = total_seconds % 60
            return f"{h:02d}{m:02d}{s:02d}"
        return t.strftime("%H%M%S")
    except:
        return "000000"

def format_sex(jk):
    return 'M' if jk == 'L' else ('F' if jk == 'P' else 'O')

def safe_str(val, fallback=""):
    return (str(val).strip() if val else fallback)


# =========================
# SEND TO ORTHANC
# =========================
def send_to_orthanc(payload):
    try:
        res = requests.post(
            f"{ORTHANC_URL}/api/ris-worklist",
            json=payload,
            auth=(ORTHANC_USER, ORTHANC_PASS),
            timeout=10
        )
        return res.status_code == 200, res.text
    except Exception as e:
        return False, str(e)


# =========================
# MAIN SYNC
# =========================
def run_sync():
    reset_cache_if_new_day()

    log("=" * 60)
    log(f"Memulai sinkronisasi... (cache: {len(SENT_CACHE)} sudah terkirim hari ini)")

    try:
        conn = mysql.connector.connect(
            **DB_CONFIG,
            charset='utf8mb4',
            collation='utf8mb4_general_ci'
        )
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci")

        query = """
        SELECT
            pr.noorder,
            pr.no_rawat,
            p.nm_pasien,
            p.no_rkm_medis,
            p.tgl_lahir,
            p.jk,
            pr.tgl_permintaan,
            pr.jam_permintaan,
            pr.informasi_tambahan,
            jpr.nm_perawatan,
            jpr.kd_jenis_prw,
            COALESCE(pr.diagnosa_klinis, pr.informasi_tambahan, '') AS indikasi,
            COALESCE(dk.nm_dokter, rp.kd_dokter, '')         AS dokter_pengirim

        FROM permintaan_radiologi pr
        JOIN reg_periksa rp      ON pr.no_rawat      = rp.no_rawat
        JOIN pasien p            ON rp.no_rkm_medis  = p.no_rkm_medis
        LEFT JOIN permintaan_pemeriksaan_radiologi ppr
                                 ON pr.noorder       = ppr.noorder
        LEFT JOIN jns_perawatan_radiologi jpr
                                 ON ppr.kd_jenis_prw = jpr.kd_jenis_prw
        LEFT JOIN dokter dk      ON rp.kd_dokter     = dk.kd_dokter
        WHERE pr.tgl_permintaan >= CURDATE()
        ORDER BY pr.tgl_permintaan ASC, pr.jam_permintaan ASC
        """

        cursor.execute(query)
        rows = cursor.fetchall()
        cursor.close()
        conn.close()

        if not rows:
            log("Tidak ada data permintaan radiologi hari ini.")
            return

        # Pisahkan: yang belum dikirim vs yang sudah ada di cache
        pending = [r for r in rows if not already_sent(safe_str(r['noorder']))]
        skipped = len(rows) - len(pending)

        log(f"Total DB: {len(rows)} | Skip (sudah terkirim): {skipped} | Akan dikirim: {len(pending)}")

        if not pending:
            log("Semua data sudah terkirim, tidak ada yang baru.")
            return

        sent_table_rows = []
        count_ok   = 0
        count_fail = 0

        for i, row in enumerate(pending, 1):
            noorder         = safe_str(row['noorder'])
            nm_perawatan    = safe_str(row.get('nm_perawatan'))
            info_tambahan   = safe_str(row.get('informasi_tambahan'))
            indikasi        = safe_str(row.get('indikasi'))
            dokter_pengirim = safe_str(row.get('dokter_pengirim'))
            nm_pasien       = safe_str(row.get('nm_pasien')).upper()

            requested_procedure = (nm_perawatan or info_tambahan or "PEMERIKSAAN").upper()

            payload = {
                "AccessionNumber":                  noorder,
                "PatientName":                      nm_pasien,
                "PatientID":                        safe_str(row.get('no_rkm_medis')),
                "PatientBirthDate":                 format_date(row.get('tgl_lahir')),
                "PatientSex":                       format_sex(row.get('jk')),
                "RequestedProcedureDescription":    requested_procedure,
                "Modality":                         MODALITY_MAPPING.get(
                                                        safe_str(row.get('kd_jenis_prw')), 'CR'
                                                    ),
                "ScheduledStationAETitle":          "KHANZA",
                "ScheduledProcedureStepStartDate":  format_date(row.get('tgl_permintaan')),
                "ScheduledProcedureStepStartTime":  format_time(row.get('jam_permintaan')),
                "StudyInstanceUID":                 generate_uid_from_str(noorder),
                "ReferringPhysicianName":           dokter_pengirim,
                "ScheduledPerformingPhysicianName": "",
            }

            success, response = send_to_orthanc(payload)

            # Retry sekali jika SQLite busy
            if not success and "1009" in str(response):
                log(f"[{i:>3}] SQLite busy, retry dalam 2 detik...", "WARN")
                time.sleep(2)
                success, response = send_to_orthanc(payload)

            if success:
                count_ok += 1
                mark_sent(noorder)   # ← masuk cache, TIDAK akan dikirim lagi
                log(f"[{i:>3}] OK   → {noorder} | {nm_pasien} | {requested_procedure}", "OK")
                sent_table_rows.append((
                    noorder,
                    nm_pasien,
                    requested_procedure,
                    info_tambahan   or "-",
                    indikasi        or "-",
                    dokter_pengirim or "-",
                ))
            else:
                count_fail += 1
                # TIDAK masuk cache → akan dicoba lagi di siklus berikutnya
                log(f"[{i:>3}] FAIL → {noorder} | {nm_pasien} | {response}", "FAIL")

            time.sleep(0.3)

        # --- Ringkasan ---
        log(f"Selesai | Terkirim: {count_ok} | Gagal: {count_fail} | Total cache hari ini: {len(SENT_CACHE)}")

        if sent_table_rows:
            log("Data yang berhasil dikirim pada siklus ini:")
            log_table(sent_table_rows)

    except mysql.connector.Error as db_err:
        log(f"Database error: {db_err}", "ERROR")
    except Exception as e:
        log(f"Unexpected error: {e}", "ERROR")


# =========================
# RUNNER
# =========================
if __name__ == "__main__":
    log("=== AUTO WORKLIST SERVICE START ===")
    log(f"Interval sinkronisasi : {INTERVAL} detik")
    log(f"Orthanc URL           : {ORTHANC_URL}")
    log(f"Database              : {DB_CONFIG['host']} / {DB_CONFIG['database']}")

    while True:
        try:
            run_sync()
        except Exception as e:
            log(f"Loop error (akan coba lagi): {e}", "ERROR")
        time.sleep(INTERVAL)