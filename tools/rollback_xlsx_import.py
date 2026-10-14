import os
import shutil

BASE_DIR = r'D:\AI\SSK\Sahajanand_Sahitya_Kendra'
DB_PATH = os.path.join(BASE_DIR, 'ssk_store.sqlite')
BACKUP_PATH = os.path.join(BASE_DIR, 'ssk_store.backup_before_xlsx_import.sqlite')

if os.path.exists(BACKUP_PATH):
    print(f"Restoring {DB_PATH} from {BACKUP_PATH}...")
    shutil.copyfile(BACKUP_PATH, DB_PATH)
    print("Database successfully restored to pre-import state.")
else:
    print(f"Error: Backup file {BACKUP_PATH} does not exist.")
