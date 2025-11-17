# HESK Upgrade Guide

This guide explains how to upgrade HESK while preserving all customizations.

## Prerequisites

- Git installed
- PowerShell (for Windows) or Bash (for Linux/Mac)
- Backup of your current database

## Upgrade Steps

### 1. Backup Current Installation

```powershell
# Backup database
mysqldump -u root -p erazim > backup_before_upgrade.sql

# Backup custom directory (if needed)
Copy-Item -Recurse custom custom_backup
```

### 2. Replace HESK Core Files

**IMPORTANT**: Back up `hesk_settings.inc.php` before deleting core files!

```powershell
# Save settings file
Copy-Item hesk_settings.inc.php hesk_settings.inc.php.backup

# Delete ALL HESK core files and directories (except custom/)
# Keep: custom/, db/, patches/, scripts/, *.md files
Get-ChildItem -Exclude custom,db,patches,scripts,*.md,hesk_settings.inc.php.backup | Remove-Item -Recurse -Force

# Extract new HESK version to current directory
# (Download and extract HESK 3.x.x here)

# Restore settings
Copy-Item hesk_settings.inc.php.backup hesk_settings.inc.php
```

### 3. Run Database Migrations (if needed)

If upgrading from 3.4.x to 3.5.0, run these migrations:

```powershell
# Run from MySQL client or command line
mysql -u root -p erazim < db/hesk_35_upgrade.sql
mysql -u root -p erazim < db/migrate_customers_to_35.sql  
mysql -u root -p erazim < db/hesk_35_auth_tokens.sql
```

For other version upgrades, check `db/` directory for new migration files.

### 4. Re-apply Core Patches

Run the patch application script:

```powershell
.\scripts\apply_patches.ps1
```

Or manually apply each patch:

```powershell
git apply patches/00-loader-create.diff
git apply patches/01-loader.diff
git apply patches/02-latest-mode.diff
git apply patches/03-latest-order.diff
git apply patches/04-admin-reply-lastchange.diff
git apply patches/05-admin-ticket-lastchange.diff
git apply patches/06-reply-ticket-lastchange.diff
git apply patches/07-posting-lastchange.diff
git apply patches/08-pipe-lastchange.diff
```

### 5. Resolve Patch Conflicts (if any)

If a patch fails, it means HESK changed those lines. You'll need to:

1. Check the patch file to see what it was trying to change
2. Manually apply the change to the new HESK version
3. Update the patch file for future upgrades:
   ```bash
   git diff -- inc/common.inc.php > patches/01-loader.diff
   ```

### 6. Verify Installation

1. Clear HESK cache: Delete files in `cache/` directory
2. Visit admin panel (`http://yourdomain/erazim/admin/`)
3. Check that custom loader is working:
   - Open browser console (F12)
   - No JavaScript errors should appear
4. Verify custom files are loaded:
   ```powershell
   # Check if loader exists
   Test-Path inc/loader_custom.php
   
   # Check if all custom modules exist
   Get-ChildItem custom/*.php
   ```
5. Check database triggers are active:
   ```sql
   SHOW TRIGGERS WHERE `Trigger` LIKE 'hesk_%updates%';
   -- Should show multiple triggers
   ```

### 7. Test Core Features

Basic HESK functionality:
- [ ] Login works
- [ ] Can view ticket list
- [ ] Can view ticket details
- [ ] Can reply to tickets
- [ ] Can create new tickets
- [ ] Categories and priorities work

Custom features:
- [ ] "Všechny poslední" button appears in ticket list
- [ ] "Moje poslední" button appears in ticket list  
- [ ] Clicking "Všechny poslední" shows all tickets sorted by most recent edit
- [ ] Clicking "Moje poslední" shows only tickets edited by current user
- [ ] Customer names display correctly in ticket headers (not "[Customer]")

### 8. Test Last-Changed Tracking

Perform these actions and verify ticket appears in correct "latest" filters:
- [ ] Create a new ticket → appears in "Všechny poslední"
- [ ] Reply to ticket → updates timestamp
- [ ] Add a note → updates timestamp
- [ ] Change category/priority → updates timestamp
- [ ] Add attachment → updates timestamp
- [ ] Edit reply → updates timestamp
- [ ] Switch to different staff user
- [ ] Edit same ticket as new user → appears in their "Moje poslední"
- [ ] Original user's "Moje poslední" no longer shows this ticket

## Troubleshooting

### Patches won't apply

**Symptom**: `git apply` reports conflicts

**Solution**: 
1. The core file has changed. Manually inspect the patch and apply changes.
2. Regenerate the patch after manual fix: `git diff -- <file> > patches/<name>.diff`

### Buttons don't appear

**Symptom**: "Všechny poslední" / "Moje poslední" buttons missing

**Solution**:
1. Check `inc/common.inc.php` has the loader include (line ~2942)
2. Check `inc/loader_custom.php` exists and loads `custom/` files
3. Check browser console for JavaScript errors
4. Verify `custom/po1_pulsanti_ultimi.php` exists and is valid PHP

### Latest filters not ordering correctly

**Symptom**: Tickets not sorted by last changed

**Solution**:
1. Check `hesk_ticket_updates` table exists and has data
2. Verify triggers are installed: `SHOW TRIGGERS;`
3. Check `custom/po3_lastchange_bootstrap.php` is enabled (remove `return;` at top if present)
4. Verify `inc/print_tickets.inc.php` has latest-mode JOIN logic
5. Verify `inc/prepare_ticket_search.inc.php` has latest-order override

### Database errors

**Symptom**: SQL errors about missing columns/tables

**Solution**:
1. Ensure `hesk_tickets.lastchange_by` column exists:
   ```sql
   ALTER TABLE hesk_tickets ADD COLUMN IF NOT EXISTS lastchange_by MEDIUMINT UNSIGNED NULL AFTER lastchange;
   ```
2. Ensure `hesk_ticket_updates` table exists (should be auto-created by `po3_lastchange_bootstrap.php`)
3. Re-run trigger installation from `db/triggers.sql`

## Patch File Format

Patches are standard Git unified diff format. Example:

```diff
diff --git a/inc/common.inc.php b/inc/common.inc.php
@@ -2940,0 +2942,4 @@
+$customLoaderPath = dirname(__FILE__) . '/loader_custom.php';
+if (file_exists($customLoaderPath)) {
+    require_once $customLoaderPath;
+}
```

## Maintenance

### Adding New Core Modifications

If you need to modify core files in the future:

1. Make the change
2. Generate patch: `git diff -- <file> > patches/XX-description.diff`
3. Document the change in this file
4. Commit both the patch and this documentation

### Updating Custom Code

Custom code in `custom/` can be modified freely - it's fully versioned and won't be overwritten during upgrades.

