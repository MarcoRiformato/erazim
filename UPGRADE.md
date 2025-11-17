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

1. Download the new HESK version
2. Extract it to a temporary location
3. Copy all files **except**:
   - `custom/` directory (keep your version)
   - `hesk_settings.inc.php` (keep your configuration)
   - Any uploaded attachments, cache, etc.

### 3. Re-apply Core Patches

Run the patch application script:

```powershell
.\scripts\apply_patches.ps1
```

Or manually:

```bash
git apply patches/01-loader.diff
git apply patches/02-latest-order.diff
# ... any other patches
```

### 4. Resolve Patch Conflicts (if any)

If a patch fails, it means HESK changed those lines. You'll need to:

1. Check the patch file to see what it was trying to change
2. Manually apply the change to the new HESK version
3. Update the patch file for future upgrades:
   ```bash
   git diff -- inc/common.inc.php > patches/01-loader.diff
   ```

### 5. Verify Installation

1. Clear HESK cache: Delete files in `cache/` directory
2. Visit admin panel and verify:
   - "Všechny poslední" button appears
   - "Moje poslední" button appears
   - Both filters work correctly
3. Check database triggers are active:
   ```sql
   SHOW TRIGGERS WHERE `Trigger` LIKE 'hesk_%updates%';
   ```

### 6. Test Functionality

- Create a new ticket
- Edit a ticket (category, priority, reply, note, attachment)
- Verify it appears in "Všechny poslední"
- Switch users and verify "Moje poslední" shows only that user's edits

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

