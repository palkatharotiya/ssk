import os
import re
import shutil
import sqlite3
import zipfile
import xml.etree.ElementTree as ET

# 1. Paths
BASE_DIR = r'D:\AI\SSK\Sahajanand_Sahitya_Kendra'
DB_PATH = os.path.join(BASE_DIR, 'ssk_store.sqlite')
BACKUP_PATH = os.path.join(BASE_DIR, 'ssk_store.backup_before_xlsx_import.sqlite')
XLSX_PATH = os.path.join(BASE_DIR, 'assets', 'images', 'ID_sahitya_OK.xlsx')
PRODUCTS_IMG_DIR = os.path.join(BASE_DIR, 'assets', 'images', 'products')

# 2. Create Database Backup
print(f"Backing up database to: {BACKUP_PATH}")
shutil.copyfile(DB_PATH, BACKUP_PATH)
print("Backup created successfully.")

# 3. Read XLSX
with zipfile.ZipFile(XLSX_PATH, 'r') as z:
    shared_strings = []
    if 'xl/sharedStrings.xml' in z.namelist():
        tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
        for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
            t = si.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
            if t is not None:
                shared_strings.append(t.text or '')
            else:
                parts = [p.text for p in si.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') if p.text]
                shared_strings.append(''.join(parts))

    sheet = ET.fromstring(z.read('xl/worksheets/sheet1.xml'))
    rows = []
    for row in sheet.findall('.//{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
        row_data = []
        for c in row.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            t_attr = c.get('t')
            v = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            val = v.text if v is not None else ''
            if t_attr == 's' and val.isdigit():
                idx = int(val)
                row_data.append(shared_strings[idx] if idx < len(shared_strings) else val)
            else:
                row_data.append(val)
        rows.append(row_data)

header = rows[0]
products_raw = rows[1:]
print(f"Total rows in spreadsheet: {len(products_raw)}")

# 4. Department Mapping
DEPT_MAP = {
    'કંઠી': 2,           # Kanthis
    'માળા': 3,           # Japamalas
    'ધાર્મિક બુક': 1,     # Books
    'પૂજા સામગ્રી': 5,     # Puja Items
    'આરતી સામગ્રી': 5,     # Puja Items
    'સ્ટીલ': 5,           # Puja Items
    'પીતળ': 6,           # Murtis
    'મૂર્તિ': 6,          # Murtis
    'એક્રેલિક મૂર્તિ': 6,    # Murtis
    'દિવ્ય સુગંધ': 7,      # Agarbatti & Dhoop
    'બાલુડા વાઘા': 8,     # Clothing
    'ગરમ વાઘા': 8,       # Clothing
    'ફેબ્રિક': 8,          # Clothing
    'બાલુડા આભુષણ': 8,     # Clothing / Ornaments
    'બાલુડા શણગાર': 8,     # Clothing / Shringar
    'જનરલ સ્ટેશનરી': 9,    # Calendars & Diaries / Stationery
    'ઈલેક્ટ્રોનીક્સ': 10,  # Audio & Video / Electronics
    'ગીફ્ટ & નોવેલ્ટી': 5, # Puja Items / Gifts
    'હેલ્થ કેર': 5,       # Puja Items / Health Care
    'other': 5,           # Puja Items
}

# 5. Image Directory & Fallbacks
all_images = [f for f in os.listdir(PRODUCTS_IMG_DIR) if f.lower().endswith(('.png', '.jpg', '.jpeg', '.webp'))]

CATEGORY_DEFAULTS = {
    'કંઠી': '0000148_tulasi-kanthi-9-6-mm--1000x1250.png',
    'માળા': 'tulsi_mala.png',
    'મૂર્તિ': 'swaminarayan_murti.png',
    'એક્રેલિક મૂર્તિ': 'swaminarayan_murti.png',
    'પીતળ': 'Betha_Ganapati_-_3_400_gm_1-Photoroom.png',
    'પૂજા સામગ્રી': 'pooja_thali.png',
    'આરતી સામગ્રી': 'pooja_thali.png',
    'ધાર્મિક બુક': 'vachanamrut.png',
    'દિવ્ય સુગંધ': 'Attar_Champa.png',
    'બાલુડા વાઘા': 'baluda_vagha.png',
    'ગરમ વાઘા': 'winter_vagha.png',
    'બાલુડા આભુષણ': 'baluda_white.png',
    'બાલુડા શણગાર': 'baluda_white.png',
    'ફેબ્રિક': 'dhoti_34.png',
    'ઈલેક્ટ્રોનીક્સ': 'mantra_dhun.png',
    'હેલ્થ કેર': 'balm_yellow.png',
    'જનરલ સ્ટેશનરી': 'Pocket_Diary.jpg',
    'ગીફ્ટ & નોવેલ્ટી': 'night_lamp.png',
    'સ્ટીલ': 'pooja_thali.png',
    'other': 'prod_1.png'
}

KEYWORDS = {
    'kesari': 'kesari',
    'sukhad': 'sukhad',
    'tulsi': 'tulasi',
    'tulasi': 'tulasi',
    'cream': 'cream',
    'champa': 'champa',
    'meera': 'meera',
    'exotic': 'exotic',
    'phulwadi': 'phulwadi',
    'raatrani': 'raatrani',
    'white ood': 'white_ood',
    'magnet': 'magnet',
    '24 carat': '24_carat',
    'ganapati': 'ganapati',
    'ganesh': 'ganapati',
    'hanuman': 'hanuman',
    'radha': 'radha',
    'krishna': 'krishna',
    'balm': 'balm',
    'night lamp': 'night_lamp',
    'mantra dhun': 'mantra_dhun',
    'dhoti': 'dhoti',
    'vagha': 'vagha',
    'winter vagha': 'winter_vagha',
    'baluda': 'baluda',
    'shikshapatri': 'shikshapatri',
    'vachanamrut': 'vachanamrut',
    'harililamrut': 'harililamrut',
    'swami ni vato': 'swami_ni_vato',
    'japmala': 'japmala',
    'mala': 'mala',
    'thali': 'thali',
    'pooja': 'pooja',
    'kirtan': 'kirtan',
    'diary': 'diary',
    'stand': 'book_stand',
}

def tokenize(text):
    return set(re.findall(r'[a-zA-Z0-9]+', str(text).lower()))

def find_image(pid, name_en, name_gu, cat):
    combined_text = f"{pid} {name_en} {name_gu}".lower()
    best_candidate = None
    best_score = 0
    for f in all_images:
        f_lower = f.lower()
        score = 0
        if pid and pid.lower() in f_lower:
            score += 100
        for k, v in KEYWORDS.items():
            if k in combined_text and v in f_lower:
                score += 35
        p_tokens = tokenize(name_en)
        f_tokens = tokenize(f)
        common = p_tokens.intersection(f_tokens) - {'mm', 'inch', 'gm', 'size', 'colour', 'color', 'and', 'with', 'the', 'for', 'x'}
        score += len(common) * 15
        if score > best_score:
            best_score = score
            best_candidate = f
    if best_score >= 30 and best_candidate:
        return best_candidate
    return CATEGORY_DEFAULTS.get(cat, 'swaminarayan_murti.png')

def extract_price(p, pid):
    price_raw = p[4].strip() if len(p) > 4 else ''
    clean_p = re.sub(r'[^\d.]', '', price_raw)
    if clean_p:
        try:
            val = float(clean_p)
            if val > 0:
                return val
        except ValueError:
            pass
    m = re.search(r'K(\d+)$', pid, re.IGNORECASE)
    if m:
        return float(m.group(1))
    return 50.0

def slugify(text):
    text = re.sub(r'[^\w\s-]', '', str(text).lower())
    return re.sub(r'[-\s]+', '-', text).strip('-')

# 6. Database Connection
conn = sqlite3.connect(DB_PATH)
cursor = conn.cursor()

# Map categories
cat_id_map = {}
for cat_name, dept_id in DEPT_MAP.items():
    cursor.execute("SELECT id FROM categories WHERE name = ? OR name LIKE ?", (cat_name, f"{cat_name}%"))
    row = cursor.fetchone()
    if row:
        cat_id_map[cat_name] = row[0]
    else:
        # Create new category
        c_slug = slugify(cat_name) or f"cat-{len(cat_id_map)+1}"
        cursor.execute("""
            INSERT INTO categories (department_id, name, slug, description, sort_order, is_active, created_at)
            VALUES (?, ?, ?, ?, 10, 1, datetime('now'))
        """, (dept_id, cat_name, c_slug, f"Sacred {cat_name} collection at SSK Store."))
        cat_id_map[cat_name] = cursor.lastrowid
        print(f"Created category: {cat_name} (ID: {cat_id_map[cat_name]}) in dept {dept_id}")

conn.commit()

# 7. Import Products
inserted = 0
updated = 0
featured_count = 0

for i, p in enumerate(products_raw):
    pid = p[0].strip() if len(p) > 0 else f"SSK-{i+1000}"
    cat = p[1].strip() if len(p) > 1 else 'other'
    name_gu = p[2].strip() if len(p) > 2 else ''
    name_en = p[3].strip() if len(p) > 3 else ''
    
    if not name_en and not name_gu:
        continue
        
    dept_id = DEPT_MAP.get(cat, 5)
    category_id = cat_id_map.get(cat, 12)
    
    # Product display name: bilingual where applicable
    if name_en and name_gu and name_en != name_gu:
        full_name = f"{name_en} ({name_gu})"
    else:
        full_name = name_en or name_gu
        
    slug_base = slugify(name_en or name_gu)
    slug = f"{slug_base}-{slugify(pid)}"
    
    price = extract_price(p, pid)
    # Slight discount for on-sale items
    is_featured = 1 if (i % 8 == 0) else 0
    on_sale = 1 if (i % 5 == 0) else 0
    sale_price = round(price * 0.88, 2) if on_sale else None
    
    short_desc_en = p[5].strip() if len(p) > 5 else ''
    short_desc_gu = p[6].strip() if len(p) > 6 else ''
    short_description = f"{short_desc_en}\n{short_desc_gu}".strip()
    
    full_desc_en = p[7].strip() if len(p) > 7 else ''
    full_desc_gu = p[8].strip() if len(p) > 8 else ''
    description = f"{full_desc_en}\n\n{full_desc_gu}".strip()
    
    image_filename = find_image(pid, name_en, name_gu, cat)
    
    # Check if product with this SKU already exists
    cursor.execute("SELECT id FROM products WHERE sku = ?", (pid,))
    existing = cursor.fetchone()
    
    if existing:
        cursor.execute("""
            UPDATE products SET
                department_id = ?,
                category_id = ?,
                name = ?,
                slug = ?,
                short_description = ?,
                description = ?,
                price = ?,
                sale_price = ?,
                stock_qty = 50,
                image = ?,
                is_active = 1,
                is_featured = ?,
                on_sale = ?,
                updated_at = datetime('now')
            WHERE id = ?
        """, (dept_id, category_id, full_name, slug, short_description, description, price, sale_price, image_filename, is_featured, on_sale, existing[0]))
        updated += 1
    else:
        cursor.execute("""
            INSERT INTO products (
                department_id, category_id, name, slug, short_description, description,
                sku, price, sale_price, cost_price, stock_qty, min_order_qty,
                weight, image, gallery, is_active, is_featured, on_sale, views,
                created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, 0.0, 50, 1,
                0.2, ?, '[]', 1, ?, ?, 0,
                datetime('now'), datetime('now')
            )
        """, (dept_id, category_id, full_name, slug, short_description, description, pid, price, sale_price, image_filename, is_featured, on_sale))
        inserted += 1

conn.commit()

cursor.execute("SELECT COUNT(*) FROM products WHERE is_active = 1")
total_active = cursor.fetchone()[0]

print("\n" + "="*50)
print(f"IMPORT COMPLETE!")
print(f"Products Inserted: {inserted}")
print(f"Products Updated:  {updated}")
print(f"Total Active Products in DB: {total_active}")
print("="*50)

conn.close()
