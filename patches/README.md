# Patch Files

These patch files contain the minimal core modifications needed for the "last changed" tracking feature.

## Current Patches

### Infrastructure Patches
- `00-loader-create.diff` - **Creates** `inc/loader_custom.php` file that loads all `custom/*.php` files
- `01-loader.diff` - Adds `require_once` for loader to `inc/common.inc.php`

### Feature Patches (Last Changed Tracking)
- `02-latest-mode.diff` - Adds latest-mode SQL JOIN logic and `lastchange_by` to SELECT in `inc/print_tickets.inc.php`
- `03-latest-order.diff` - Adds latest-order override to `inc/prepare_ticket_search.inc.php`
- `04-admin-reply-lastchange.diff` - Adds `lastchange_by` update to `admin/admin_reply_ticket.php`
- `05-admin-ticket-lastchange.diff` - Adds `lastchange_by` updates to `admin/admin_ticket.php` (2 locations)
- `06-reply-ticket-lastchange.diff` - Adds `lastchange_by = 0` update to `reply_ticket.php`
- `07-posting-lastchange.diff` - Adds `lastchange_by` variable and INSERT column to `inc/posting_functions.inc.php`
- `08-pipe-lastchange.diff` - Adds `lastchange_by = 0` update to `inc/pipe_functions.inc.php`

## Important: Regenerating Patches

**These patch files are placeholders** based on the current modified code. When you upgrade to a new HESK version, you should regenerate them against the fresh HESK files:

1. Make your core modifications to the fresh HESK install
2. Generate new patches:
   ```bash
   # Infrastructure patches
   git diff -- inc/loader_custom.php > patches/00-loader-create.diff
   git diff -- inc/common.inc.php > patches/01-loader.diff
   
   # Feature patches
   git diff -- inc/print_tickets.inc.php > patches/02-latest-mode.diff
   git diff -- inc/prepare_ticket_search.inc.php > patches/03-latest-order.diff
   git diff -- admin/admin_reply_ticket.php > patches/04-admin-reply-lastchange.diff
   git diff -- admin/admin_ticket.php > patches/05-admin-ticket-lastchange.diff
   git diff -- reply_ticket.php > patches/06-reply-ticket-lastchange.diff
   git diff -- inc/posting_functions.inc.php > patches/07-posting-lastchange.diff
   git diff -- inc/pipe_functions.inc.php > patches/08-pipe-lastchange.diff
   ```

## Patch Format

Patches use standard Git unified diff format. They can be applied with:
```bash
git apply patches/01-loader.diff
```

Or use the helper script:
```powershell
.\scripts\apply_patches.ps1
```

