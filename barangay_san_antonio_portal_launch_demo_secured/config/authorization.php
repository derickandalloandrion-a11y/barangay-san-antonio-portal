<?php
declare(strict_types=1);
return [
    'official_permissions' => [
        'source' => 'position_permissions',
        'require_exactly_one_verified_active_assignment' => true,
        'use_generic_role_permissions_for_officials' => false,
    ],
];
