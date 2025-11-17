# Initial Setup Guide

This guide explains how to set up this repository for the first time, or after getting a fresh HESK installation.

## First-Time Setup

### 1. Install Fresh HESK

1. Download the latest HESK version
2. Extract it to your project directory
3. Run the HESK installer (via web interface) to set up the database

### 2. Apply Core Patches

Run the patch application script:

```powershell
.\scripts\apply_patches.ps1
```

Or manually:
```bash
git apply patches/01-loader.diff
git apply patches/02-latest-mode.diff
git apply patches/03-latest-order.diff
```

**Note**: If patches fail to apply, it means HESK has changed those files. You'll need to:
1. Manually apply the changes described in each patch file
2. Regenerate the patch: `git diff -- <file> > patches/<name>.diff`

### 3. Verify Custom Loader

Ensure `inc/loader_custom.php` exists. If not, create it (it should have been created automatically, but verify).

### 4. Initialize Database

The custom bootstrap (`custom/po3_lastchange_bootstrap.php`) will automatically:
- Create `hesk_ticket_updates` table
- Install all necessary triggers

Just visit any admin page and the bootstrap will run automatically.

### 5. Verify Installation

1. Visit the admin ticket list page
2. Verify "Všechny poslední" and "Moje poslední" buttons appear
3. Test both filters to ensure they work correctly

## After HESK Upgrade

See `UPGRADE.md` for detailed upgrade instructions.

## Troubleshooting

### Patches won't apply

If a patch fails, it means HESK changed those lines. You have two options:

1. **Manual application**: Open the patch file, see what it's trying to change, and manually apply those changes to the new HESK files
2. **Regenerate patch**: After manually applying, regenerate: `git diff -- <file> > patches/<name>.diff`

### Buttons don't appear

1. Check `inc/common.inc.php` has the loader include (around line 2942)
2. Check `inc/loader_custom.php` exists
3. Check `custom/po1_pulsanti_ultimi.php` exists and is valid PHP
4. Check browser console for JavaScript errors

### Database errors

1. Ensure `hesk_tickets.lastchange_by` column exists:
   ```sql
   ALTER TABLE hesk_tickets ADD COLUMN IF NOT EXISTS lastchange_by MEDIUMINT UNSIGNED NULL AFTER lastchange;
   ```
2. Visit an admin page to trigger the bootstrap (creates `hesk_ticket_updates` table and triggers)

