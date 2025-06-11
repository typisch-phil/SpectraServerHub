<?php
// Zentrale Zeitzone-Hilfsfunktionen für SpectraHost
// Alle Zeiten werden in deutscher Zeit (Europe/Berlin) angezeigt

function formatGermanDateTime($datetime, $format = 'd.m.Y, H:i \U\h\r') {
    if (empty($datetime)) {
        return '';
    }
    
    try {
        // Erstelle DateTime-Objekt und konvertiere zur deutschen Zeitzone
        $dt = new DateTime($datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Europe/Berlin'));
        return $dt->format($format);
    } catch (Exception $e) {
        // Fallback: Verwende die originale Zeit und füge 2 Stunden hinzu
        return date($format, strtotime($datetime . ' +2 hours'));
    }
}

function formatGermanDate($datetime) {
    return formatGermanDateTime($datetime, 'd.m.Y H:i');
}

function formatGermanTimeShort($datetime) {
    return formatGermanDateTime($datetime, 'H:i');
}
?>