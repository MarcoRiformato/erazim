#!/bin/bash
# Bash script to apply all patches to HESK core files
# Usage: ./scripts/apply_patches.sh

set -e

echo "Applying HESK customization patches..."

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PATCHES_DIR="$SCRIPT_DIR/../patches"

if [ ! -d "$PATCHES_DIR" ]; then
    echo "Error: Patches directory not found: $PATCHES_DIR" >&2
    exit 1
fi

PATCHES=($(find "$PATCHES_DIR" -name "*.diff" -type f | sort))

if [ ${#PATCHES[@]} -eq 0 ]; then
    echo "No patch files found in $PATCHES_DIR"
    exit 0
fi

echo "Found ${#PATCHES[@]} patch file(s)"

FAILED=()
APPLIED=()

for patch in "${PATCHES[@]}"; do
    echo ""
    echo "Applying: $(basename "$patch")"
    
    if git apply --check "$patch" 2>/dev/null; then
        if git apply "$patch"; then
            echo "  ✓ Applied successfully"
            APPLIED+=("$(basename "$patch")")
        else
            echo "  ✗ Failed to apply"
            FAILED+=("$(basename "$patch")")
        fi
    else
        echo "  ✗ Patch conflicts or file missing"
        echo "    You may need to manually apply this patch"
        FAILED+=("$(basename "$patch")")
    fi
done

echo ""
echo "=================================================="
echo "Summary:"
echo "  Applied: ${#APPLIED[@]}"
echo "  Failed:  ${#FAILED[@]}"

if [ ${#FAILED[@]} -gt 0 ]; then
    echo ""
    echo "Failed patches:"
    for f in "${FAILED[@]}"; do
        echo "  - $f"
    done
    echo ""
    echo "Please review UPGRADE.md for manual patch application instructions."
    exit 1
fi

echo ""
echo "All patches applied successfully!"
echo "Next: Clear HESK cache and verify functionality."

