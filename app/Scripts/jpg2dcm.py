import sys
import pydicom
from pydicom.dataset import Dataset, FileDataset
from pydicom.uid import ExplicitVRLittleEndian
import pydicom.uid
from PIL import Image
import datetime
import os

def jpg_to_dcm(jpg_path, dcm_path, metadata):
    try:
        # Load Image
        im = Image.open(jpg_path)
        
        # Determine if we should use RGB or Monochrome
        # For simplicity and standard radiology, we convert to grayscale (L)
        if im.mode != 'L':
            im = im.convert('L')
        
        # Get image data
        pixels = im.tobytes()
        
        # Create dataset
        file_meta = Dataset()
        file_meta.MediaStorageSOPClassUID = pydicom.uid.SecondaryCaptureImageStorage
        file_meta.MediaStorageSOPInstanceUID = pydicom.uid.generate_uid()
        file_meta.ImplementationClassUID = pydicom.uid.generate_uid()
        file_meta.TransferSyntaxUID = ExplicitVRLittleEndian

        ds = FileDataset(dcm_path, {}, file_meta=file_meta, preamble=b"\0" * 128)
        
        # Add metadata
        ds.PatientName = metadata.get('PatientName', 'ANONYMOUS').upper()
        ds.PatientID = metadata.get('PatientID', '000000')
        ds.PatientBirthDate = metadata.get('PatientBirthDate', '').replace('-', '')
        ds.PatientSex = metadata.get('PatientSex', 'O')
        ds.AccessionNumber = metadata.get('AccessionNumber', '')
        ds.StudyInstanceUID = metadata.get('StudyInstanceUID', pydicom.uid.generate_uid())
        ds.SeriesInstanceUID = pydicom.uid.generate_uid()
        ds.SOPInstanceUID = file_meta.MediaStorageSOPInstanceUID
        ds.SOPClassUID = file_meta.MediaStorageSOPClassUID
        
        now = datetime.datetime.now()
        ds.ContentDate = now.strftime('%Y%m%d')
        ds.ContentTime = now.strftime('%H%M%S.%f')
        ds.StudyDate = ds.ContentDate
        ds.StudyTime = ds.ContentTime
        
        ds.Modality = metadata.get('Modality', 'OT')
        ds.SeriesDescription = "Converted from JPG"
        ds.StudyDescription = metadata.get('StudyDescription', "Radiology Study")
        
        ds.SamplesPerPixel = 1
        ds.PhotometricInterpretation = "MONOCHROME2"
        ds.PixelRepresentation = 0
        ds.HighBit = 7
        ds.BitsStored = 8
        ds.BitsAllocated = 8
        ds.SmallestImagePixelValue = 0
        ds.LargestImagePixelValue = 255
        ds.Columns = im.width
        ds.Rows = im.height
        ds.PixelData = pixels
        
        ds.save_as(dcm_path)
        print(f"SUCCESS: {dcm_path}")
    except Exception as e:
        print(f"ERROR: {str(e)}")
        sys.exit(1)

if __name__ == "__main__":
    if len(sys.argv) < 9:
        print("Usage: jpg2dcm.py <input> <output> <name> <id> <dob> <sex> <acc> <studyuid> [modality] [description]")
        sys.exit(1)
        
    in_path = sys.argv[1]
    out_path = sys.argv[2]
    meta = {
        'PatientName': sys.argv[3],
        'PatientID': sys.argv[4],
        'PatientBirthDate': sys.argv[5],
        'PatientSex': sys.argv[6],
        'AccessionNumber': sys.argv[7],
        'StudyInstanceUID': sys.argv[8],
        'Modality': sys.argv[9] if len(sys.argv) > 9 else 'OT',
        'StudyDescription': sys.argv[10] if len(sys.argv) > 10 else 'Radiology Study',
    }
    
    jpg_to_dcm(in_path, out_path, meta)
