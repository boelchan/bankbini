import json
import re

EXTRACTED_PATH = "scratch/indonesia_villages_border.geojson"
OUTPUT_GEOJSON = "public/pragaan_desa.json"

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

print("Searching for PRAGAAN villages...")
features = []

with open(EXTRACTED_PATH, "r", encoding="utf-8") as f:
    text = f.read()

# Pattern to extract individual json objects for PRAGAAN
# Each object is {"province":"...","district":"...","sub_district":"PRAGAAN",...}
pattern = re.compile(r'\{[^{}]*?"sub_district"\s*:\s*"PRAGAAN"[^{}]*?"border"\s*:\s*(\[[^}]+?\])\s*\}', re.IGNORECASE)

# Or find "sub_district":"PRAGAAN" and expand backwards to { and forwards to }
pos = 0
matches = []
target = '"sub_district":"PRAGAAN"'
while True:
    idx = text.find(target, pos)
    if idx == -1:
        # Try with space
        target2 = '"sub_district": "PRAGAAN"'
        idx = text.find(target2, pos)
        if idx == -1:
            break
        idx_target = idx
        pos = idx + len(target2)
    else:
        idx_target = idx
        pos = idx + len(target)

    # Find starting {
    start = text.rfind('{', 0, idx_target)
    # Find ending } by matching brackets
    depth = 0
    end = start
    for i in range(start, len(text)):
        if text[i] == '{':
            depth += 1
        elif text[i] == '}':
            depth -= 1
            if depth == 0:
                end = i + 1
                break
    
    obj_str = text[start:end]
    try:
        obj = json.loads(obj_str)
        matches.append(obj)
    except Exception as e:
        print(f"Error parsing object: {e}")

print(f"Total matching objects found: {len(matches)}")

for item in matches:
    dist = str(item.get("district", "")).strip().upper()
    sub = str(item.get("sub_district", "")).strip().upper()
    v_raw = str(item.get("village", "")).strip().upper()
    title_name = v_raw.title()
    code = VILLAGE_CODES.get(v_raw)
    border = item.get("border", [])

    if border and border[0] != border[-1]:
        border.append(border[0])

    feature = {
        "type": "Feature",
        "id": code or v_raw,
        "properties": {
            "code": code,
            "village_code": code,
            "name": title_name,
            "village": title_name,
            "sub_district": "Pragaan",
            "district": dist.title(),
            "province": "Jawa Timur",
            "NAMOBJ": title_name,
            "WADMKD": title_name,
            "WADMKC": "Pragaan",
            "WADMKK": dist.title(),
            "WADMPR": "Jawa Timur"
        },
        "geometry": {
            "type": "Polygon",
            "coordinates": [border]
        }
    }
    features.append(feature)
    print(f"[OK] {title_name} (Kab: {dist}, Points: {len(border)})")

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

print(f"\nSaved {len(features)} village boundaries to {OUTPUT_GEOJSON}")
