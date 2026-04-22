#!/usr/bin/env python3
"""
img_to_dicom.py  - pydicom 3.x compatible, Windows-safe
Convert a JPG/PNG image to a DICOM file and upload it to Orthanc via /instances.

Usage (file mode): python img_to_dicom.py --tags-file <tags.json> --image <path> --url <url> --user <u> --pass <p>
"""

import sys
import os
import json
import io
import datetime
import argparse
import requests

try:
    from PIL import Image
    import numpy as np
    import pydicom
    from pydicom.dataset import Dataset, FileDataset, FileMetaDataset
    from pydicom.uid import generate_uid, ExplicitVRLittleEndian
except ImportError as e:
    print(json.dumps({"success": False, "error": f"Missing library: {e}"}))
    sys.exit(1)


def make_dicom_from_image(image_path, tags):
    now = datetime.datetime.now()

    img = Image.open(image_path)
    if img.mode in ('RGBA', 'P', 'LA'):
        img = img.convert('RGB')
    elif img.mode not in ('RGB', 'L'):
        img = img.convert('RGB')

    img_array = np.array(img)
    is_color = len(img_array.shape) == 3 and img_array.shape[2] == 3

    sop_instance_uid = generate_uid()
    sop_class_uid    = '1.2.840.10008.5.1.4.1.1.7'  # Secondary Capture

    file_meta = FileMetaDataset()
    file_meta.MediaStorageSOPClassUID    = sop_class_uid
    file_meta.MediaStorageSOPInstanceUID = sop_instance_uid
    file_meta.TransferSyntaxUID          = ExplicitVRLittleEndian
    file_meta.ImplementationClassUID     = '1.2.3.4.5.6.7'
    file_meta.ImplementationVersionName  = 'RIS_UPLOAD_1'

    ds = FileDataset(None, {}, file_meta=file_meta,
                     is_implicit_VR=False, is_little_endian=True)

    ds.SOPClassUID    = sop_class_uid
    ds.SOPInstanceUID = sop_instance_uid

    ds.PatientName      = tags.get('PatientName', 'Anonymous')
    ds.PatientID        = tags.get('PatientID', '000000')
    ds.PatientBirthDate = tags.get('PatientBirthDate', '')
    ds.PatientSex       = tags.get('PatientSex', 'O')

    ds.StudyInstanceUID  = generate_uid()
    ds.StudyDate         = tags.get('StudyDate', now.strftime('%Y%m%d'))
    ds.StudyTime         = tags.get('StudyTime', now.strftime('%H%M%S'))
    ds.StudyDescription  = tags.get('StudyDescription', 'Manual Upload')
    ds.AccessionNumber   = tags.get('AccessionNumber', '')

    ds.SeriesInstanceUID  = generate_uid()
    ds.SeriesDate         = ds.StudyDate
    ds.SeriesTime         = ds.StudyTime
    ds.SeriesDescription  = tags.get('SeriesDescription', 'Uploaded Images')
    ds.Modality           = tags.get('Modality', 'OT').upper()
    ds.SeriesNumber       = '1'
    ds.InstanceNumber     = '1'

    ds.SamplesPerPixel           = 3 if is_color else 1
    ds.PhotometricInterpretation = 'RGB' if is_color else 'MONOCHROME2'
    ds.Rows                      = int(img_array.shape[0])
    ds.Columns                   = int(img_array.shape[1])
    ds.BitsAllocated             = 8
    ds.BitsStored                = 8
    ds.HighBit                   = 7
    ds.PixelRepresentation       = 0
    if is_color:
        ds.PlanarConfiguration   = 0

    ds.PixelData = img_array.tobytes()
    return ds


def upload_to_orthanc(ds, orthanc_url, user, password):
    buf = io.BytesIO()
    pydicom.dcmwrite(buf, ds)
    buf.seek(0)

    resp = requests.post(
        orthanc_url.rstrip('/') + '/instances',
        data=buf.read(),
        headers={'Content-Type': 'application/dicom'},
        auth=(user, password),
        timeout=30
    )
    if resp.status_code in (200, 201):
        return {"success": True, "instance": resp.json()}
    else:
        return {"success": False, "error": resp.text, "status": resp.status_code}


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--image',      required=True)
    parser.add_argument('--tags-file',  required=True, dest='tags_file')
    parser.add_argument('--url',        required=True)
    parser.add_argument('--user',       required=True)
    parser.add_argument('--pass',       required=True, dest='password')
    args = parser.parse_args()

    if not os.path.isfile(args.image):
        print(json.dumps({"success": False, "error": f"Image not found: {args.image}"}))
        sys.exit(1)

    if not os.path.isfile(args.tags_file):
        print(json.dumps({"success": False, "error": f"Tags file not found: {args.tags_file}"}))
        sys.exit(1)

    with open(args.tags_file, 'r', encoding='utf-8') as f:
        tags = json.load(f)

    try:
        ds     = make_dicom_from_image(args.image, tags)
        result = upload_to_orthanc(ds, args.url, args.user, args.password)
        print(json.dumps(result))
    except Exception as e:
        import traceback
        print(json.dumps({"success": False, "error": str(e),
                          "trace": traceback.format_exc()}))
        sys.exit(1)


if __name__ == '__main__':
    main()
