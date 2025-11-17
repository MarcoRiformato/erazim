# HESK Customization - Last Changed Tracking

This repository contains customizations for HESK (Help Desk Software) that add "last changed" tracking functionality with two new filters:
- **Všechny poslední** (All Latest): Shows all tickets ordered by most recent edit, regardless of who made it
- **Moje poslední** (My Latest): Shows tickets last updated by the currently logged-in staff member

## Repository Structure

- `custom/` - All custom PHP code (versioned)
- `patches/` - Git patch files for minimal core hooks (versioned)
- `db/` - Database schema files (triggers.sql) (versioned)
- `scripts/` - Helper scripts for upgrades (versioned)
- HESK core files - **NOT versioned** (excluded via .gitignore)

## Quick Start

1. **Install HESK** - Drop a fresh HESK installation into this directory
2. **Apply patches** - Run `scripts/apply_patches.ps1` (or manually: `git apply patches/*.diff`)
3. **Verify** - The custom loader will automatically set up database triggers on first run

## Upgrade Workflow

See `UPGRADE.md` for detailed upgrade instructions.

## How It Works

- **Database**: Uses `hesk_ticket_updates` table with triggers on `hesk_tickets`, `hesk_replies`, `hesk_notes`, `hesk_attachments`, and `hesk_reply_drafts`
- **Custom Code**: All logic in `custom/` directory, loaded automatically via `inc/loader_custom.php`
- **Core Hooks**: Minimal patches to core files (see `patches/` directory)

