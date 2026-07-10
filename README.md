# Kalmoy POS

PHP/MySQL point-of-sale system for retail (admin, POS, inventory, sales, debt/credit, reports).

| Environment | URL |
|-------------|-----|
| Local XAMPP | `http://localhost/BestPosKalmoy/` (folder name may differ) |
| Production | https://kalmoypos.infinityfreeapp.com/ |

---

## 1. Project overview

- Entry: `index.php` (storefront) and `admin/index.php` (admin/POS)
- Bootstrap: `config.php` → `initialize.php` → DB via `classes/DBConnection.php`
- Local DB name: **`ash_pos_db`**
- Production DB name: **`if0_42375362_kalmoyposdb`**

---

## 2. Local XAMPP setup

1. Place the project under `C:\xampp7\htdocs\` (or your XAMPP `htdocs`).
2. Start **Apache** and **MySQL** in XAMPP.
3. Create the database (phpMyAdmin or MySQL):

```sql
CREATE DATABASE IF NOT EXISTS ash_pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

4. Import **`database/production_schema.sql`** into `ash_pos_db`.
5. Create the first administrator (required — schema has no default login):

```powershell
php -r "echo password_hash('YOUR_STRONG_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Copy `database/create_production_admin.private.sql.example` → `database/create_production_admin.private.sql`, paste the hash, import that SQL, then delete the private file if it leaves your machine.

6. Ensure local config exists:

- `config.local.php` — host `localhost`, user `root`, empty password, database `ash_pos_db`
- If missing, copy `config.local.php.example` → `config.local.php`

7. Open:

- Home: `http://localhost/BestPosKalmoy/`
- Admin: `http://localhost/BestPosKalmoy/admin/login.php`

Login fails safely until an administrator row exists (empty `users` table is supported).

---

## 3. Configuration (local + production)

| File | Purpose | In Git? |
|------|---------|---------|
| `config.php` | App helpers + loads DB connection | Yes |
| `initialize.php` | Environment detection + loads local/production config | Yes |
| `config.local.php` | XAMPP credentials | **No** (gitignored) |
| `config.production.php` | InfinityFree credentials | **No** (gitignored) |
| `config.local.php.example` | Local template | Yes |
| `config.production.php.example` | Production template | Yes |

`initialize.php` auto-selects:

- **localhost** → `config.local.php` (fallback: `initialize.local.php`, then built-in XAMPP defaults)
- **InfinityFree / production** → `config.production.php` (fallback: `initialize.production.php`)

`base_url` is detected from the request host and document root (works for both local subfolders and the live domain).

---

## 4. GitHub initialization and push (FRESH HISTORY — required)

The old remote (`cosmetics_pos`) and old Git history must **not** be pushed to the public repo.
Use a brand-new Git repository from the cleaned working tree:

Recommended repository: **https://github.com/caano-geel/Kalmoy-POS.git**

```powershell
cd C:\xampp7\htdocs\BestPosKalmoy

# 1) Remove old Git history (keeps your files; deletes .git only)
Remove-Item -Recurse -Force .git

# 2) Create a fresh repository
git init
git add .
git status
# Confirm these are NOT staged:
#   config.local.php, config.production.php, .env*, uploads/,
#   database/create_production_admin.private.sql, backup_production_config/

git commit -m "Initial public release of Kalmoy POS for InfinityFree deployment"
git branch -M main
git remote add origin https://github.com/caano-geel/Kalmoy-POS.git
git push -u origin main
```

If the GitHub repo already has commits, use a force push only if you intentionally want to replace them:

```powershell
git push -u origin main --force
```

Do **not** commit `config.local.php`, `config.production.php`, or any real passwords.

---

## 5. GitHub repository secrets

Settings → Secrets and variables → Actions:

| Secret name | Value |
|-------------|--------|
| `FTP_HOST` | `ftpupload.net` |
| `FTP_USERNAME` | `if0_42375362` |
| `FTP_PASSWORD` | `PLACE_REAL_FTP_PASSWORD_HERE` |

---

## 6. InfinityFree deployment steps

See **[DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md)** for full detail.

Summary:

1. Push to `main` → GitHub Actions deploys code to `./htdocs/`
2. Manually create `htdocs/config.production.php` (from example; set real MySQL password)
3. Manually import `database/production_schema.sql` into `if0_42375362_kalmoyposdb`
4. Manually upload logo/images into `htdocs/uploads/` (uploads are excluded from FTP sync)

---

## 7. Production database import

- SQL file: **`database/production_schema.sql`**
- Target DB: **`if0_42375362_kalmoyposdb`**
- Host: **`sql110.infinityfree.com`**
- Method: InfinityFree phpMyAdmin → Import
- GitHub Actions **never** imports or overwrites MySQL data

---

## 8. Production configuration file

Create on the server:

**`htdocs/config.production.php`**

Use the exact template in `config.production.php.example`, with:

- Host: `sql110.infinityfree.com`
- Username: `if0_42375362`
- Database: `if0_42375362_kalmoyposdb`
- Password: your real InfinityFree password (not stored in Git)

---

## 9. Auto-deployment workflow

File: `.github/workflows/deploy.yml`

- Trigger: push to `main`
- Action: `SamKirkland/FTP-Deploy-Action@v4.3.5`
- Remote directory: `./htdocs/`
- Excludes secrets, SQL, `uploads/`, `.github/`, and local-only tooling so production runtime files are preserved

---

## 10. Future updates

```powershell
git add .
git commit -m "Describe update"
git push origin main
```

Code updates deploy automatically. **MySQL data does not auto-sync** — schema/data changes must be applied manually in phpMyAdmin when needed.

---

## 11. Security notes

- Repository is public: no real passwords, FTP credentials, or private customer dumps in Git
- Production errors do not display database passwords
- Sensitive config files are blocked by `.htaccess`

---

## Related docs

- [LOCAL_SETUP.md](LOCAL_SETUP.md) — local XAMPP notes
- [OFFLINE_SETUP.md](OFFLINE_SETUP.md) — offline XAMPP copy
- [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) — InfinityFree production steps
