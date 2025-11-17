<?php

if (!defined('IN_SCRIPT')) {
    return;
}

$po2Script = isset($_SERVER['SCRIPT_NAME']) ? basename($_SERVER['SCRIPT_NAME']) : '';

if ($po2Script !== 'admin_ticket.php') {
    return;
}

$po2TrackId = hesk_cleanID('track');
if ($po2TrackId === false) {
    $po2TrackId = '';
}

$GLOBALS['hesk_custom_po2_trackid'] = $po2TrackId;
$GLOBALS['hesk_custom_po2_style_injected'] = false;

if (!function_exists('hesk_custom_po2_inline_callback')) {
    function hesk_custom_po2_inline_callback($buffer)
    {
        if (strpos($buffer, 'block--uploads') === false) {
            return $buffer;
        }

        $pattern = '/<div class="block--uploads"[^>]*>.*?<\/div>/s';
        if (!preg_match_all($pattern, $buffer, $matches, PREG_OFFSET_CAPTURE)) {
            return $buffer;
        }

        foreach (array_reverse($matches[0]) as $match) {
            $rawBlock = $match[0];
            $startPos = $match[1];
            $inlineMarkup = hesk_custom_po2_build_inline_markup($rawBlock);

            if ($inlineMarkup === '') {
                continue;
            }

            // Remove original attachment list block
            $buffer = substr_replace($buffer, '', $startPos, strlen($rawBlock));

            $before = substr($buffer, 0, $startPos);
            $insertPos = strrpos($before, '</div>');
            if ($insertPos === false) {
                $insertPos = $startPos;
            }

            $buffer = substr_replace($buffer, $inlineMarkup, $insertPos, 0);
        }

        return $buffer;
    }

    function hesk_custom_po2_build_inline_markup($rawBlock)
    {
        if (!preg_match_all('/download_attachment\.php\?att_id=([0-9]+)/i', $rawBlock, $attMatches)) {
            return '';
        }

        $inlineItems = array();

        foreach ($attMatches[1] as $attId) {
            $fileData = hesk_custom_po2_fetch_attachment((int) $attId);
            if (!$fileData) {
                continue;
            }

            $downloadName = hesk_htmlspecialchars($fileData['real_name']);
            $dataUri = 'data:' . $fileData['mime'] . ';base64,' . $fileData['encoded'];
            $type = hesk_custom_po2_determine_type($fileData['mime']);

            if ($type === 'image') {
                $content = '<img class="inline-attachment__preview" src="' . $dataUri . '" alt="' . $downloadName . '">';
            } elseif ($type === 'pdf') {
                $content = '<object data="' . $dataUri . '" type="application/pdf" class="inline-attachment__preview inline-attachment__preview--pdf"><p><a href="' . $dataUri . '" download="' . $downloadName . '">' . $downloadName . '</a></p></object>';
            } else {
                $content = '<a class="inline-attachment__link" href="' . $dataUri . '" download="' . $downloadName . '">' . $downloadName . '</a>';
            }

            $inlineItems[] = '
                <div class="inline-attachment inline-attachment--' . $type . '">
                    <div class="inline-attachment__header">
                        <svg class="icon icon-attach"><use xlink:href="' . HESK_PATH . 'img/sprite.svg#icon-attach"></use></svg>
                        <span>' . $downloadName . '</span>
                    </div>
                    <div class="inline-attachment__body">' . $content . '</div>
                </div>
            ';
        }

        if (!$inlineItems) {
            return '';
        }

        $styleBlock = '';
        if (empty($GLOBALS['hesk_custom_po2_style_injected'])) {
            $GLOBALS['hesk_custom_po2_style_injected'] = true;
            $styleBlock = '<style>
                .inline-attachments { margin: 12px 0 0; display: flex; flex-direction: column; gap: 12px; }
                .inline-attachment { border: 1px solid #dfe7f3; border-radius: 8px; background: #f8fbff; }
                .inline-attachment__header { padding: 8px 12px; display: flex; align-items: center; gap: 8px; font-weight: 600; color: #1f3b73; border-bottom: 1px solid #dfe7f3; }
                .inline-attachment__header svg { width: 16px; height: 16px; fill: #1f3b73; }
                .inline-attachment__body { padding: 12px; }
                .inline-attachment__preview { max-width: 100%; height: auto; border-radius: 6px; display: block; }
                .inline-attachment__preview--pdf { width: 100%; min-height: 400px; border: none; }
                .inline-attachment__link { font-weight: 600; color: #1f3b73; text-decoration: none; }
                .inline-attachment__link:hover { text-decoration: underline; }
            </style>';
        }

        return $styleBlock . '<div class="inline-attachments">' . implode('', $inlineItems) . '</div>';
    }

    function hesk_custom_po2_fetch_attachment($attId)
    {
        static $cache = array();

        if (isset($cache[$attId])) {
            return $cache[$attId];
        }

        global $hesk_settings;

        $trackId = isset($GLOBALS['hesk_custom_po2_trackid']) ? $GLOBALS['hesk_custom_po2_trackid'] : '';
        $res = hesk_dbQuery("SELECT `saved_name`, `real_name`, `ticket_id` FROM `".hesk_dbEscape($hesk_settings['db_pfix'])."attachments` WHERE `att_id`=" . intval($attId) . " LIMIT 1");

        if (!hesk_dbNumRows($res)) {
            return $cache[$attId] = null;
        }

        $file = hesk_dbFetchAssoc($res);
        if ($trackId !== '' && $file['ticket_id'] != $trackId) {
            return $cache[$attId] = null;
        }

        $path = HESK_PATH . $hesk_settings['attach_dir'] . '/' . $file['saved_name'];
        if (!is_file($path)) {
            return $cache[$attId] = null;
        }

        $mime = hesk_custom_po2_detect_mime($path);
        $encoded = base64_encode(file_get_contents($path));

        return $cache[$attId] = array(
            'encoded' => $encoded,
            'mime' => $mime,
            'real_name' => $file['real_name'],
        );
    }

    function hesk_custom_po2_detect_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if ($mime) {
                return $mime;
            }
        } elseif (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            if ($mime) {
                return $mime;
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'pdf':
                return 'application/pdf';
            case 'jpg':
            case 'jpeg':
                return 'image/jpeg';
            case 'png':
                return 'image/png';
            case 'gif':
                return 'image/gif';
            default:
                return 'application/octet-stream';
        }
    }
}

function hesk_custom_po2_determine_type($mime)
{
    if (strpos($mime, 'image/') === 0) {
        return 'image';
    }

    if ($mime === 'application/pdf') {
        return 'pdf';
    }

    return 'generic';
}

ob_start('hesk_custom_po2_inline_callback');

