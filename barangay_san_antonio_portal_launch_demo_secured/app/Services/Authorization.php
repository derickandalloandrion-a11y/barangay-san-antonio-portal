<?php
declare(strict_types=1);

/** Fail-closed RBAC. Officials are authorized only through one verified active position assignment. */
final class Authorization
{
    private static function schema(PDO $db): SchemaInspector { return new SchemaInspector($db); }

    public static function user(PDO $db, int $userId): ?array
    {
        if ($userId <= 0) return null;
        $s = self::schema($db);
        $required = [
            'users' => ['user_id','username','email','account_type','account_status','email_verified_at'],
            'user_roles' => ['user_id','role_id'],
            'roles' => ['role_id','role_code','role_name','is_active'],
        ];
        foreach ($required as $table => $columns) {
            if (!$s->hasColumns($table, $columns)) throw new RuntimeException('The connected database is missing required RBAC structure: '.$table.'.');
        }
        $q = $db->prepare("SELECT u.user_id,u.username,u.email,u.account_type,u.account_status,u.email_verified_at,
                                  r.role_id,r.role_code,r.role_name
                           FROM users u
                           JOIN user_roles ur ON ur.user_id=u.user_id
                           JOIN roles r ON r.role_id=ur.role_id AND r.is_active=1
                           WHERE u.user_id=:uid AND u.account_status='ACTIVE' AND u.email_verified_at IS NOT NULL
                           ORDER BY r.role_code");
        $q->execute(['uid'=>$userId]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) !== 1) return null;
        $user = $rows[0];
        $expected = [
            'RESIDENT'=>'RESIDENT', 'VISITOR'=>'VISITOR',
            'OFFICIAL'=>'BARANGAY_OFFICIAL', 'ADMINISTRATOR'=>'BARANGAY_ADMINISTRATOR'
        ];
        if (($expected[$user['account_type']] ?? null) !== $user['role_code']) return null;
        $user['position_id'] = null;
        $user['position_name'] = null;
        $user['official_assignment_id'] = null;
        $user['position_assignment_valid'] = false;

        if ($user['role_code'] === 'BARANGAY_OFFICIAL') {
            if (!$s->hasColumns('official_assignments', ['official_assignment_id','user_id','position_id','term_start','term_end','assignment_status','verification_status']) ||
                !$s->hasColumns('official_positions', ['position_id','position_name','is_active'])) return $user;
            $p = $db->prepare("SELECT oa.official_assignment_id,oa.position_id,op.position_name
                               FROM official_assignments oa
                               JOIN official_positions op ON op.position_id=oa.position_id AND op.is_active=1
                               WHERE oa.user_id=:uid AND oa.assignment_status='ACTIVE' AND oa.verification_status='VERIFIED'
                                 AND oa.term_start<=CURRENT_DATE()
                                 AND (oa.term_end IS NULL OR oa.term_end>=CURRENT_DATE())");
            $p->execute(['uid'=>$userId]);
            $assignments = $p->fetchAll(PDO::FETCH_ASSOC);
            if (count($assignments) === 1) {
                $user['official_assignment_id'] = (int)$assignments[0]['official_assignment_id'];
                $user['position_id'] = (int)$assignments[0]['position_id'];
                $user['position_name'] = $assignments[0]['position_name'];
                $user['position_assignment_valid'] = true;
            }
        }
        return $user;
    }

    public static function can(PDO $db, array $user, string $permissionCode): bool
    {
        if ($permissionCode === '' || !preg_match('/^[a-z0-9_.-]{2,100}$/', $permissionCode)) return false;
        $s = self::schema($db);
        if (!$s->hasColumns('permissions', ['permission_id','permission_code'])) return false;

        if (($user['role_code'] ?? '') === 'BARANGAY_OFFICIAL') {
            // No fallback to role_permissions is permitted for Officials.
            if (empty($user['position_assignment_valid']) || empty($user['position_id']) ||
                !$s->hasColumns('position_permissions', ['position_id','permission_id']) ||
                !$s->hasColumns('official_positions', ['position_id','is_active'])) return false;
            $q = $db->prepare("SELECT 1
                               FROM position_permissions pp
                               JOIN official_positions op ON op.position_id=pp.position_id AND op.is_active=1
                               JOIN permissions p ON p.permission_id=pp.permission_id
                               WHERE pp.position_id=:pid AND p.permission_code=:code LIMIT 1");
            $q->execute(['pid'=>$user['position_id'],'code'=>$permissionCode]);
            return (bool)$q->fetchColumn();
        }

        if (empty($user['role_id']) || !$s->hasColumns('role_permissions', ['role_id','permission_id']) ||
            !$s->hasColumns('user_roles', ['user_id','role_id'])) return false;
        $q = $db->prepare("SELECT 1 FROM user_roles ur
                           JOIN role_permissions rp ON rp.role_id=ur.role_id
                           JOIN permissions p ON p.permission_id=rp.permission_id
                           WHERE ur.user_id=:uid AND ur.role_id=:rid AND p.permission_code=:code LIMIT 1");
        $q->execute(['uid'=>$user['user_id'],'rid'=>$user['role_id'],'code'=>$permissionCode]);
        return (bool)$q->fetchColumn();
    }

    public static function require(PDO $db, array $user, string $permissionCode): void
    {
        if (!self::can($db,$user,$permissionCode)) {
            http_response_code(403);
            exit('You do not have permission to perform this action.');
        }
    }
}
