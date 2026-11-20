<?php
declare(strict_types=1);
final class SchemaHealth
{
    public static function inspect(PDO $db): array
    {
        $dbName=(string)$db->query('SELECT DATABASE()')->fetchColumn();
        $q=$db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_type='BASE TABLE'");$q->execute([$dbName]);$tables=(int)$q->fetchColumn();
        $q=$db->prepare("SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=? AND constraint_type='CHECK'");$q->execute([$dbName]);$checks=(int)$q->fetchColumn();
        $q=$db->prepare("SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=? AND constraint_type='FOREIGN KEY'");$q->execute([$dbName]);$fks=(int)$q->fetchColumn();
        return ['database'=>$dbName,'tables'=>$tables,'foreign_keys'=>$fks,'check_constraints'=>$checks,'expected_demo_tables'=>27,'launch_demo_schema_detected'=>($dbName==='barangay_san_antonio_portal_launch_demo'&&$tables===27)];
    }
}
