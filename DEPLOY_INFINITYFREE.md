# Kalmoy POS — InfinityFree Deployment

Public site: https://kalmoypos.infinityfreeapp.com/

## Important

- **Code** deploys via GitHub Actions FTP.
- **MySQL data does not auto-sync.** Import SQL once manually in phpMyAdmin.
- Never put FTP or MySQL passwords in the GitHub repository.

## 1. First-time InfinityFree setup

### A. Create production config (required)

Using File Manager or FTP, create:

`htdocs/config.production.php`

Paste the contents from `config.production.php.example` in this repo, then set the real password:

```php
<?php
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production');
}
if (!defined('DB_SERVER')) {
    define('DB_SERVER', 'sql110.infinityfree.com');
}
if (!defined('DB_HOST')) {
    define('DB_HOST', 'sql110.infinityfree.com');
}
if (!defined('DB_PORT')) {
    define('DB_PORT', 3306);
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', 'if0_42375362');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'if0_42375362_kalmoyposdb');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'PLACE_REAL_INFINITYFREE_PASSWORD_HERE');
}
if (!defined('DB_SSL')) {
    define('DB_SSL', false);
}
if (!defined('DB_SSL_CA')) {
    define('DB_SSL_CA', '');
}
```

Replace `PLACE_REAL_INFINITYFREE_PASSWORD_HERE` with your InfinityFree account / vPanel password.

### B. Import database (manual, once)

1. Open InfinityFree vPanel → phpMyAdmin
2. Select database: `if0_42375362_kalmoyposdb`
3. Import: `database/production_schema.sql` from this project (upload from your PC; **not** deployed by GitHub Actions)
4. Create the first administrator (schema has **no** default login):

```powershell
php -r "echo password_hash('YOUR_STRONG_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Copy `database/create_production_admin.private.sql.example` → a private SQL file, paste the bcrypt hash, import it, then delete the private file.

The login page works with an empty `users` table (sign-in fails until an admin exists).

### C. Uploads folder (runtime) vs branding (deployed)

GitHub Actions **does not overwrite** `htdocs/uploads/` (excluded on purpose — preserves live product images, backups, import temp).

**Initial branding** (logo, cover, brand image, scanner sound) is committed under **`assets/branding/`** and is deployed with the code. `production_schema.sql` points `system_info` / brands at those paths.

After go-live, new uploads still go to `uploads/` as usual (writable folder). Create empty `htdocs/uploads/` (and subfolders) on the server if missing, with write permission.

## 2. GitHub Actions secrets

Repository → Settings → Secrets and variables → Actions:

| Secret | Value |
|--------|--------|
| `FTP_HOST` | `ftpupload.net` |
| `FTP_USERNAME` | `if0_42375362` |
| `FTP_PASSWORD` | *(your InfinityFree FTP password — never commit)* |

Pushing to `main` runs `.github/workflows/deploy.yml` and syncs code into `./htdocs/`.

## 3. What is never deployed / never deleted by FTP

Excluded from sync (so production secrets and runtime files stay safe):

- `config.production.php`, `config.local.php`
- `initialize.production.php`, `initialize.local.php`
- `.env*`
- `database/**` and all `*.sql`
- `uploads/**` (images, backups, import temp)
- `.github/**`, docs, and local-only tooling

## 4. Post-deploy checklist

- [ ] https://kalmoypos.infinityfreeapp.com/ loads
- [ ] Admin login works
- [ ] POS sale completes
- [ ] Images load (after uploading `uploads/`)
- [ ] No database password shown on error pages

## 5. Troubleshooting

**Database connection error**

- Confirm `htdocs/config.production.php` exists with the correct password
- Host must be `sql110.infinityfree.com`
- Database must be `if0_42375362_kalmoyposdb`

**Blank page**

- Check InfinityFree error logs
- Confirm PHP files landed in `htdocs/` root (not `htdocs/BestPosKalmoy/`)
