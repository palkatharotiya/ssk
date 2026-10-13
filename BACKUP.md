# SSK Store — Backup & Disaster Recovery Guide
**Website**: Shri Sahajanand Sahitya Kendra (Kalakunj Mandir, Surat)  
**Target Audience**: Store Administrator & Mandir IT Team

---

## 1. What Needs Backing Up?

The SSK Store consists of two critical data components:
1. **The Database (`baps_store.sqlite`)**: Contains all products, categories, customer orders, shipping addresses, admin users, banners, and coupons.
2. **The Uploads Folder (`uploads/`)**: Contains all uploaded product images, scripture covers, and banner graphics.

The PHP source code files rarely change after go-live; backing up the **database** and **uploads directory** guarantees complete recovery.

---

## 2. Recommended Backup Schedule

| Frequency | Target Data | Retention Period | Reason |
| :--- | :--- | :--- | :--- |
| **Daily** | `baps_store.sqlite` | Keep 30 daily backups | Protects recent customer orders and stock updates. |
| **Weekly** | Full zip of `uploads/` folder | Keep 8 weekly backups | Covers new product photos and banner uploads. |
| **Before Any Update** | Full site backup (files + database) | Keep indefinitely | Essential for instant rollback before making changes. |

---

## 3. How to Back Up on Shared Hosting (cPanel)

### Method A: One-Click File Manager Download (Quickest)
1. Log in to cPanel.
2. Open **File Manager** and navigate to your store folder.
3. Locate `baps_store.sqlite`: Right-click and choose **Download**. Save this file on your local computer or secure Google Drive.
4. Locate the `uploads/` folder: Right-click, select **Compress**, choose `.zip`, and name it `uploads-backup-[DATE].zip`. Once compressed, right-click the zip file and click **Download**.

### Method B: Automated cPanel Daily Backups
1. In cPanel, look for **Backup Wizard** or **JetBackup / cPBackup**.
2. Enable automatic daily backups to your hosting account or external Cloud storage (e.g. Google Drive, AWS S3, or Dropbox).

---

## 4. How to Back Up on a VPS (Linux Command Line)

### Automated Daily Cron Job Script
Create a backup script at `/usr/local/bin/ssk_backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/sskstore"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"

# 1. Back up SQLite database cleanly
sqlite3 /var/www/sskstore/baps_store.sqlite ".backup '$BACKUP_DIR/db_$DATE.sqlite'"

# 2. Back up uploaded media
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C /var/www/sskstore uploads

# 3. Purge backups older than 30 days
find "$BACKUP_DIR" -type f -mtime +30 -delete

echo "SSK Store backup completed successfully at $DATE"
```
Make it executable:
```bash
chmod +x /usr/local/bin/ssk_backup.sh
```
Add to `crontab -e` to run automatically every night at 2:00 AM:
```cron
0 2 * * * /usr/local/bin/ssk_backup.sh > /var/log/ssk_backup.log 2>&1
```

---

## 5. How to Restore from Backup

### Restoring the Database
1. If the database file is corrupted or orders are accidentally modified:
2. Ensure no customer is actively checking out (you can temporarily place a maintenance notice).
3. In File Manager or via SFTP, rename the broken `baps_store.sqlite` to `baps_store_corrupted.sqlite`.
4. Upload your saved backup copy and rename it to `baps_store.sqlite`.
5. Ensure file permissions are set to `644` (or `664`) so the web server can read and write to it.
6. Open your store in the browser and verify product listings and orders.

### Restoring the Uploads Folder
1. In File Manager or terminal, extract your backup `uploads-[DATE].zip` directly into the site root folder.
2. Verify that images in `uploads/products/` and `uploads/banners/` are restored.
3. Ensure the folder permissions are set to `755` (or `775`).

---

## 6. Testing Your Backups

A backup is only as good as its restore test. **Once every 3 months**:
1. Download a fresh backup of `baps_store.sqlite` to your local computer.
2. Open it in a free viewer (e.g., [DB Browser for SQLite](https://sqlitebrowser.org/)).
3. Verify that the `orders`, `products`, and `admin_users` tables are intact and readable.
