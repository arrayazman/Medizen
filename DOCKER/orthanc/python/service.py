import orthanc
import json
import random
import time
from datetime import datetime

# ============================================================
# UID GENERATOR
# ============================================================
def generate_uid():
    prefix = "1.2.826.0.1.3680043.2.1125"
    ts = int(time.time() * 1000)
    rand = random.randint(1000, 9999)
    return f"{prefix}.{ts}.{rand}"

def safe_get(data, key, default=None):
    value = data.get(key)
    return default if value in [None, ""] else value


# ============================================================
# CREATE DICOM FROM IMAGE
# ============================================================
def create_dicom_from_image(output, uri, **request):

    if request['method'] != 'POST':
        output.SendMethodNotAllowed('POST')
        return

    try:
        body = request['body']
        if isinstance(body, bytes):
            body = body.decode('utf-8')

        data = json.loads(body)

        # =========================
        # VALIDASI WAJIB
        # =========================
        required_fields = ["ImageBase64", "PatientID", "PatientName", "AccessionNumber"]
        for field in required_fields:
            if not data.get(field):
                output.SendHttpStatus(400, f"{field} is required")
                return

        image_base64 = data["ImageBase64"]

        if "," in image_base64:
            image_base64 = image_base64.split(",")[1]

        now = datetime.now()

        study_date = safe_get(data, "StudyDate", now.strftime("%Y%m%d"))
        study_time = safe_get(data, "StudyTime", now.strftime("%H%M%S"))

        # =========================
        # UID (BOLEH DIKIRIM DARI RIS)
        # =========================
        study_uid = safe_get(data, "StudyInstanceUID", generate_uid())
        series_uid = safe_get(data, "SeriesInstanceUID", generate_uid())
        sop_uid = generate_uid()
        frame_uid = safe_get(data, "FrameOfReferenceUID", generate_uid())

        # =========================
        # BUILD TAGS DINAMIS
        # =========================
        tags = {}

        # List tag yang boleh dikirim dari RIS
        allowed_tags = [
            "PatientName", "PatientID", "PatientBirthDate", "PatientSex",
            "AccessionNumber", "StudyDescription", "SeriesDescription",
            "Modality", "BodyPartExamined", "ViewPosition",
            "StudyID", "SeriesNumber", "InstanceNumber",
            "Manufacturer", "ProtocolName",
            "InstitutionName", "InstitutionAddress",
            "ReferringPhysicianName",
            "PerformingPhysicianName",
            "OperatorsName",
            "PresentationIntentType",
            "BurnedInAnnotation",
            "PixelSpacing"
        ]

        for tag in allowed_tags:
            val = safe_get(data, tag)
            if val is not None:
                tags[tag] = val

        # =========================
        # WAJIB DICOM (SYSTEM)
        # =========================
        tags.update({
            "SOPClassUID": safe_get(data, "SOPClassUID", "1.2.840.10008.5.1.4.1.1.1.1"),
            "SOPInstanceUID": sop_uid,

            "StudyInstanceUID": study_uid,
            "SeriesInstanceUID": series_uid,
            "FrameOfReferenceUID": frame_uid,

            "StudyDate": study_date,
            "SeriesDate": study_date,
            "AcquisitionDate": study_date,
            "ContentDate": study_date,

            "StudyTime": study_time,
            "SeriesTime": study_time,
            "AcquisitionTime": study_time,
            "ContentTime": study_time,

            "ImageType": safe_get(data, "ImageType", "DERIVED\\PRIMARY"),

            # Default minimal jika tidak dikirim RIS
            "SamplesPerPixel": safe_get(data, "SamplesPerPixel", "1"),
            "PhotometricInterpretation": safe_get(data, "PhotometricInterpretation", "MONOCHROME2"),
            "BitsAllocated": safe_get(data, "BitsAllocated", "16"),
            "BitsStored": safe_get(data, "BitsStored", "14"),
            "HighBit": safe_get(data, "HighBit", "13"),
            "PixelRepresentation": safe_get(data, "PixelRepresentation", "0")
        })

        # =========================
        # PAYLOAD
        # =========================
        payload = {
            "Force": True,
            "Tags": tags,
            "Content": "data:image/jpeg;base64," + image_base64
        }

        # =========================
        # CREATE DICOM
        # =========================
        response_json = orthanc.RestApiPost(
            "/tools/create-dicom",
            json.dumps(payload)
        )

        response_data = json.loads(response_json)
        instance_id = response_data.get("ID")

        if not instance_id:
            raise Exception("Gagal membuat DICOM")

        orthanc.LogWarning(f"[SUCCESS] DICOM created: {instance_id}")

        # =========================
        # AUTO SEND (OPSIONAL)
        # =========================
        try:
            orthanc.RestApiPost(
                f"/instances/{instance_id}/store",
                '"DCMROUTER"'
            )
            orthanc.LogWarning("[SUCCESS] Sent to DCMROUTER")
        except Exception as e:
            orthanc.LogError(f"[STORE ERROR] {str(e)}")

        # =========================
        # RESPONSE
        # =========================
        output.AnswerBuffer(json.dumps({
            "success": True,
            "OrthancID": instance_id,
            "StudyUID": study_uid,
            "SeriesUID": series_uid,
            "SOPUID": sop_uid
        }), "application/json")

    except Exception as e:
        orthanc.LogError(f"[ERROR] {str(e)}")
        output.SendHttpStatus(500, str(e))


# ============================================================
# REGISTER API
# ============================================================
orthanc.RegisterRestCallback(
    "/api/ris-upload-image",
    create_dicom_from_image
)

orthanc.LogWarning("RIS Upload API READY (FULL DINAMIS)")