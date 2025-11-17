<?php

// Inject "Moje poslední" / "Všechny poslední" buttons on ticket list pages.
// Kept VERY simple and safe: pure JS using current URL; no output buffering.

if (!defined('IN_SCRIPT')) {
    return;
}

$script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';
if ($script !== 'admin_main.php' && $script !== 'show_tickets.php' && $script !== 'find_tickets.php') {
    return;
}

?>
<script>
(function () {
    function buildUrl(mode) {
        var url = new URL(window.location.href);
        var p = url.searchParams;
        // Reset paging & sorting related to our mode
        p.set('latest', mode);
        p.set('sort', 'lastchange');
        p.set('asc', '0');
        p.set('cot', '1');
        p.set('page', '1');
        if (mode === 'my') {
            // Show all ownership buckets; filtering is done by our latest logic in SQL
            p.set('s_my', '1');
            p.set('s_ot', '1');
            p.set('s_un', '1');
        } else {
            p.delete('s_my');
            p.delete('s_ot');
            p.delete('s_un');
        }
        // Do not keep ql=... when using latest
        p.delete('ql');
        url.search = p.toString();
        return url.toString();
    }

    function injectButtons() {
        var listing = document.querySelector('.filters__listing');
        if (!listing) return;

        // Remove any previous instances of our custom buttons
        Array.prototype.slice.call(listing.querySelectorAll('a')).forEach(function (a) {
            var s = a.querySelector('span');
            if (!s) return;
            var label = s.textContent.trim();
            if (label === 'Moje poslední' || label === 'Všechny poslední') {
                a.parentNode.removeChild(a);
            }
        });

        var firstBtn = listing.querySelector('a');
        if (!firstBtn) return;

        var params = new URLSearchParams(window.location.search);
        var latest = params.get('latest');

        function makeBtn(label, mode) {
            var a = document.createElement('a');
            a.href = buildUrl(mode);
            a.className = 'btn btn-transparent';
            if (latest === mode) {
                a.className += ' is-bold is-selected';
            }
            var span = document.createElement('span');
            span.textContent = label;
            a.appendChild(span);
            return a;
        }

        var btnMy = makeBtn('Moje poslední', 'my');
        var btnAll = makeBtn('Všechny poslední', 'all');

        // Insert after the first existing button
        if (firstBtn.nextSibling) {
            listing.insertBefore(btnMy, firstBtn.nextSibling);
        } else {
            listing.appendChild(btnMy);
        }
        if (btnMy.nextSibling) {
        listing.insertBefore(btnAll, btnMy.nextSibling);
        } else {
            listing.appendChild(btnAll);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectButtons);
    } else {
        injectButtons();
    }
})();
</script>
