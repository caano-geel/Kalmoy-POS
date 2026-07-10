# Local XAMPP setup (Kalmoy POS)

## Quick start

1. Create database `ash_pos_db` (utf8mb4).
2. Import `database/production_schema.sql`.
3. Create the first administrator (no default login is shipped):

```powershell
php -r "echo password_hash('YOUR_STRONG_PASSWORD_HERE', PASSWORD_DEFAULT), PHP_EOL;"
```

Copy `database/create_production_admin.private.sql.example` → `database/create_production_admin.private.sql`, paste the hash, import it.

4. Ensure `config.local.php` exists (copy from `config.local.php.example` if needed).
5. Open `http://localhost/BestPosKalmoy/` (or your folder name under `htdocs`).

## Local database settings

| Setting  | Value        |
|----------|--------------|
| Host     | `localhost`  |
| User     | `root`       |
| Password | *(empty)*    |
| Database | `ash_pos_db` |

Loaded from `config.local.php` (gitignored). Fallback: built-in defaults in `initialize.php`.

## Notes

- `base_url` is auto-detected from the folder under `htdocs`.
- Do not upload `config.local.php` to InfinityFree.
- Full production steps: [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) and [README.md](README.md).
