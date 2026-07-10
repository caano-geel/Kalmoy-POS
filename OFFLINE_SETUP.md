# Kalmoy POS — Offline Desktop Setup

This guide installs the POS on a **local Windows PC** for shop-counter use without internet.

## Requirements

- Windows 10/11
- [XAMPP](https://www.apachefriends.org/) (PHP 7.4+ or 8.x, MariaDB/MySQL)
- Web browser (Chrome or Edge recommended)

## 1. Install XAMPP

1. Download and install XAMPP to `C:\xampp` (or `C:\xampp7`).
2. Open **XAMPP Control Panel**.
3. Start **Apache** and **MySQL**.

## 2. Copy the project

1. Copy the entire `BestPosKalmoy` folder to:
   ```
   C:\xampp\htdocs\BestPosKalmoy
   ```
2. Ensure these folders are writable:
   - `uploads/`
   - `uploads/backups/`

## 3. Create / import the database

1. Open **phpMyAdmin**: http://localhost/phpmyadmin
2. Create database: `ash_pos_db` (utf8mb4)
3. Import `database/production_schema.sql` (preferred) or a private backup.
4. Create the first administrator (no default login is shipped):

```powershell
php -r "echo password_hash('YOUR_STRONG_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Copy `database/create_production_admin.private.sql.example` → `database/create_production_admin.private.sql`, paste the hash, import it.
5. Run user-management migration only if needed (schema already includes staff columns):
   ```bash
   php database/migrate_users_management.php
   ```

## 4. Database connection

Local defaults (in `config.local.php` / `initialize.php`):

| Setting  | Value        |
|----------|--------------|
| Host     | localhost    |
| User     | root           |
| Password | *(empty)*      |
| Database | ash_pos_db     |

Optional: create `config.local.php` (preferred) or `initialize.local.php` to override credentials.

## 5. Login

1. Open: **http://localhost/BestPosKalmoy/admin/**
2. Sign in with the administrator you created in step 4.
3. There is **no** public default administrator password. Create your own admin first (see step 4).

## 6. Daily use (offline)

- All assets run locally (no CDN required for barcode scanning).
- Use **Point of Sale** for sales.
- Use **Stock & Inventory** for stock levels.
- Use **Backup & Restore** before major changes.

## 7. Backup & restore

### Create backup
Admin → **Backup & Restore** → **Create Backup**  
Files save to `uploads/backups/`.

### Restore
Backup & Restore → **Restore** on a backup row (overwrites current data).

### Clean data (owner only)
Backup & Restore → **Clean Data** section (auto-backup before each action).

## 8. User & permissions

- **Users / Staff** — create cashiers and managers (owner only).
- **Role Permissions** — default staff role + per-user overrides (owner only).
- Inactive users cannot log in.

## 9. Moving online later

1. Export database backup from the offline PC.
2. Upload project files to hosting (exclude `initialize.local.php` if it has local-only settings).
3. Import backup on the server database.
4. Update `initialize.production.php` or `.env` with hosting DB credentials.
5. Set `APP_ENV=production` if using environment config.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Blank page | Enable `display_errors` in `initialize.local.php`; check Apache error log |
| DB connection failed | Verify MySQL is running and `ash_pos_db` exists |
| Login fails | Reset password via phpMyAdmin or owner **Users / Staff** |
| Permission denied | Log in as Admin/Owner (users.type = 1) |

## Support

Developed by Abdullahi Omar Salad — Kalmoy POS v1.0
