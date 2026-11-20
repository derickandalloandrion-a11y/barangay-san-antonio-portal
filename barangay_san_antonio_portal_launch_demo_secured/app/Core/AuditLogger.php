<?php
declare(strict_types=1);
final class AuditLogger
{
    public static function record(PDO $db, ?int $userId, string $event, ?string $entityType=null, ?int $entityId=null, array $details=[]): void
    {
        $json = json_encode($details, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
        $q = $db->prepare('INSERT INTO audit_logs (user_id,event_code,entity_type,entity_id,details_json,ip_hash) VALUES (:uid,:event,:type,:id,:details,:ip)');
        $q->execute(['uid'=>$userId,'event'=>$event,'type'=>$entityType,'id'=>$entityId,'details'=>$json,'ip'=>$ipHash]);
    }
}
