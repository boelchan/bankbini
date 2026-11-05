import ast
import json
import os
import urllib.request
import zipfile

ZIP_URL = "https://raw.githubusercontent.com/pararawendy/border-desa-indonesia-geojson/master/indonesia_villages_border.geojson.zip"
ZIP_PATH = os.path.join("scratch", "indonesia_villages_border.geojson.zip")
EXTRACTED_PATH = os.path.join("scratch", "indonesia_villages_border.geojson")
OUTPUT_GEOJSON = os.path.join("public", "pragaan_desa.json")

# 1. Download zip jika belum ada
if not os.path.exists(ZIP_PATH):
    print(f"Downloading {ZIP_URL}...")
    opener = urllib.request.build_opener()
    opener.addheaders = [("User-Agent", "Mozilla/5.0")]
    urllib.request.install_opener(opener)
    urllib.request.urlretrieve(ZIP_URL, ZIP_PATH)
    print(f"Downloaded: {os.path.getsize(ZIP_PATH) / (1024*1024):.2f} MB")
else:
    print(f"Zip file exists: {os.path.getsize(ZIP_PATH) / (1024*1024):.2f} MB")

# 2. Extract
if not os.path.exists(EXTRACTED_PATH):
    print("Extracting zip file...")
    with zipfile.ZipFile(ZIP_PATH, 'r') as zip_ref:
        zip_ref.extractall("scratch")
    print("Extracted successfully.")
else:
    print("Extracted file exists.")

# 3. Read and parse
print("Reading border data...")

# Village code mapping for Kecamatan Pragaan (352911)
VILLAGE_CODES = {
    'KADUARA TIMUR': 3529112001,
    'SENDANG': 3529112002,
    'ROMBASAN': 3529112003,
    'SENTOL LAOK': 3529112004,
    'LARANGAN PERRENG': 3529112005,
    'SENTOL DAYA': 3529112006,
    'PAKAMBAN DAYA': 3529112007,
    'PAKAMBAN LAOK': 3529112008,
    'JADDUNG': 3529112009,
    'PRAGAAN LAOK': 3529112010,
    'PRAGAAN DAYA': 3529112011,
    'PRENDUAN': 3529112012,
    'AENG PANAS': 3529112013,
    'KARDULUK': 3529112014,
}

features = []

with open(EXTRACTED_PATH, "r", encoding="utf-8") as f:
    # Read and parse using ast.literal_eval or line-by-line if possible
    # Since it's a python list of dicts, let's load it
    content = f.read()
    print(f"Content length: {len(content) / (1024*1024):.2f} MB")
    data = ast.literal_eval(content)
    print(f"Total village items loaded: {len(data)}")

    for item in data:
        sub = str(item.get("sub_district", "")).strip().upper()
        dist = str(item.get("district", "")).strip().upper()

        if sub == "PRAGAAN" and ("SUMENEP" in dist or not dist):
            raw_v_name = str(item.get("village", "")).strip().upper()
            title_name = raw_v_name.title()

            code = VILLAGE_CODES.get(raw_v_name)
            border = item.get("border", [])

            # Border is typically list of [lng, lat]
            # Standard GeoJSON Polygon coordinates are [ [ [lng, lat], ... ] ]
            # Ensure closed ring (first == last)
            if border and border[0] != border[-1]:
                border.append(border[0])

            feature = {
                "type": "Feature",
                "id": code or raw_v_name,
                "properties": {
                    "code": code,
                    "village_code": code,
                    "name": title_name,
                    "village": title_name,
                    "sub_district": "Pragaan",
                    "district": "Sumenep",
                    "province": "Jawa Timur",
                    "NAMOBJ": title_name,
                    "WADMKD": title_name,
                    "WADMKC": "Pragaan",
                    "WADMKK": "Sumenep",
                    "WADMPR": "Jawa Timur"
                },
                "geometry": {
                    "type": "Polygon",
                    "coordinates": [border]
                }
            }
            features.append(feature)
            print(f"Found village: {title_name} (Code: {code}, Points: {len(border)})")

geojson_result = {
    "type": "FeatureCollection",
    "name": "Batas Desa Kecamatan Pragaan",
    "crs": {
        "type": "name",
        "properties": {"name": "urn:ogc:def:crs:OGC:1.3:CRS84"}
    },
    "features": features
}

with open(OUTPUT_GEOJSON, "w", encoding="utf-8") as out:
    json.dump(geojson_result, out, indent=2)

print(f"\nSUCCESS! Saved {len(features)} village boundaries to {OUTPUT_GEOJSON}")
