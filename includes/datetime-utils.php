<?php
// Zentrale Datums- und Zeitfunktionen für SpectraHost
// Alle Zeiten werden in der Zeitzone Europe/Berlin angezeigt

function formatDateTime($datetime, $format = 'd.m.Y, H:i \U\h\r') {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        // Erstelle DateTime-Objekt mit Berlin-Zeitzone
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Europe/Berlin'));
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime; // Fallback auf ursprünglichen Wert
    }
}

function formatDateTimeShort($datetime) {
    return formatDateTime($datetime, 'd.m.Y H:i');
}

function formatDateOnly($datetime) {
    return formatDateTime($datetime, 'd.m.Y');
}

function formatTimeOnly($datetime) {
    return formatDateTime($datetime, 'H:i');
}

function getGermanDateTime($datetime = null) {
    if ($datetime === null) {
        $datetime = 'now';
    }
    
    try {
        $dt = new DateTime($datetime, new DateTimeZone('Europe/Berlin'));
        return $dt->format('Y-m-d H:i:s');
    } catch (Exception $e) {
        return date('Y-m-d H:i:s');
    }
}

function formatTimeAgo($datetime) {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        $dt = new DateTime($datetime);
        $dt->setTimezone(new DateTimeZone('Europe/Berlin'));
        $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
        $diff = $now->diff($dt);
        
        if ($diff->days > 7) {
            return formatDateTimeShort($datetime);
        } elseif ($diff->days > 0) {
            return $diff->days . ' Tag' . ($diff->days > 1 ? 'e' : '') . ' her';
        } elseif ($diff->h > 0) {
            return $diff->h . ' Stunde' . ($diff->h > 1 ? 'n' : '') . ' her';
        } elseif ($diff->i > 0) {
            return $diff->i . ' Minute' . ($diff->i > 1 ? 'n' : '') . ' her';
        } else {
            return 'Gerade eben';
        }
    } catch (Exception $e) {
        return formatDateTimeShort($datetime);
    }
}
?>