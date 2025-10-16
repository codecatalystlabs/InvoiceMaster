# Deployment Guide - Production Server

## 🚨 Current Issue: 500 Internal Server Error

You're getting this error because the application is configured for localhost. Follow these steps to fix it.

---

## Step 1: Run Diagnostic Script

1. Upload all files to your server at `https://codecatalystug.com/invoice/`
2. Visit: `https://codecatalystug.com/invoice/debug.php`
3. This will show you what's wrong with your server setup

**Note:** The debug script will tell you:
- If PHP extensions are missing
- If database connection is failing
- If file permissions are wrong
- If composer dependencies are missing

---

## Step 2: Update Database Configuration

### Option A: Get Database Credentials from cPanel

1. Log into your cPanel
2. Go to **MySQL Databases**
3. Note down:
   - Database Host (usually `localhost`)
   - Database Name
   - Database Username
   - Database Password

### Option B: Update config.php

Edit `includes/config.php` on the server:

```php
// Database Configuration - UPDATE THESE
define('DB_HOST', 'localhost'); // or your host
define('DB_USER', 'codecata_invoice'); // your db username
define('DB_PASS', 'your_password'); // your db password
define('DB_NAME', 'codecata_invoice_system'); // your db name

// Application Settings - UPDATE THIS
define('APP_URL', 'https://codecatalystug.com/invoice');
```

You can use the `config.production.example.php` as a template.

---

## Step 3: Import Database

1. Log into **phpMyAdmin** on your hosting
2. Create a new database (if not already created)
3. Select the database
4. Click **Import**
5. Upload and import `database.sql`
6. Verify all tables were created

---

## Step 4: Upload Composer Dependencies

You need the `vendor/` folder on the server.

### Option A: Upload from Local
```bash
# Zip the vendor folder locally
tar -czf vendor.tar.gz vendor/
```
Then upload and extract on server.

### Option B: Run on Server (if you have SSH access)
```bash
cd /path/to/invoice/
composer install --no-dev --optimize-autoloader
```

---

## Step 5: Update .htaccess

Replace `.htaccess` with `.htaccess.production`:

```bash
# On server via SSH or file manager
cp .htaccess.production .htaccess
```

Or manually update line 6 in `.htaccess`:
- If in subdirectory: `RewriteBase /invoice/`
- If in root: `RewriteBase /`

---

## Step 6: Set File Permissions

Make sure these folders are writable:

```bash
chmod 755 pdf/temp/
chmod 644 includes/config.php
```

Or via File Manager in cPanel:
- `pdf/temp/` → 755 (writable)
- `includes/config.php` → 644 (readable)

---

## Step 7: Security Checklist

After everything works, DELETE these files for security:

```
❌ debug.php
❌ test_auth.php
❌ test_gd.php
❌ fix_admin_password.php
❌ config.production.example.php
❌ .htaccess.production (keep .htaccess)
❌ DEPLOYMENT_GUIDE.md (this file)
```

You can do this via cPanel File Manager or:
```bash
rm debug.php test_*.php fix_*.php config.production.example.php .htaccess.production DEPLOYMENT_GUIDE.md
```

---

## Step 8: Test the Application

1. Visit: `https://codecatalystug.com/invoice/`
2. You should be redirected to login page
3. Login with:
   - Username: `admin`
   - Password: `admin123`
4. Test creating quotations and invoices

---

## Common Issues & Solutions

### Issue: "Connection failed" Error
**Solution:** Database credentials in `config.php` are wrong. Double-check cPanel.

### Issue: "Composer not found" Error
**Solution:** Upload the `vendor/` folder manually.

### Issue: "Permission denied" writing PDFs
**Solution:** Set `pdf/temp/` permissions to 755 or 777.

### Issue: Still getting 500 error
**Solution:** 
1. Check server error logs in cPanel
2. Temporarily enable errors in `config.php`:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
3. Run `debug.php` to see detailed diagnostics

### Issue: SSL/HTTPS errors
**Solution:** Make sure your SSL certificate is installed properly in cPanel.

---

## Force HTTPS (Recommended)

Uncomment these lines in `.htaccess`:

```apache
# Force HTTPS
<IfModule mod_rewrite.c>
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

---

## Quick Checklist

- [ ] Run `debug.php` to identify issues
- [ ] Update database credentials in `includes/config.php`
- [ ] Update `APP_URL` to production URL
- [ ] Import `database.sql` into database
- [ ] Upload `vendor/` folder
- [ ] Update `.htaccess` RewriteBase
- [ ] Set `pdf/temp/` permissions to 755
- [ ] Delete all test/debug files
- [ ] Test login functionality
- [ ] Force HTTPS

---

## Need Help?

If you're still getting errors:
1. Check the output of `debug.php`
2. Check server error logs in cPanel → Error Logs
3. Share the exact error message

Good luck! 🚀

