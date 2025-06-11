<?php
// Unread ticket notifications system for SpectraHost
function getUnreadTicketCount($db, $user_id) {
    try {
        $result = $db->fetchOne("
            SELECT COUNT(*) as unread_count
            FROM support_tickets t 
            WHERE t.user_id = ? 
            AND (
                SELECT COUNT(*) 
                FROM ticket_messages tm 
                WHERE tm.ticket_id = t.id 
                AND tm.created_at > COALESCE(t.user_last_seen, '1970-01-01')
                AND tm.user_id != t.user_id
            ) > 0
        ", [$user_id]);
        
        return $result['unread_count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

function getTicketNotificationBadge($db, $user_id) {
    $unreadCount = getUnreadTicketCount($db, $user_id);
    
    if ($unreadCount > 0) {
        return '<span class="ml-1 px-2 py-1 bg-red-600 text-white text-xs rounded-full animate-pulse">' . $unreadCount . '</span>';
    }
    
    return '';
}
?>