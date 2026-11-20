<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/config/app.php';
require dirname(__DIR__).'/app/Core/Security.php';
require dirname(__DIR__).'/app/Core/Database.php';
require dirname(__DIR__).'/app/Core/View.php';
require dirname(__DIR__).'/app/Core/SchemaInspector.php';
require dirname(__DIR__).'/app/Services/Authorization.php';
require dirname(__DIR__).'/app/Services/DashboardData.php';
Security::sendSecurityHeaders($config);
Security::startSession($config);
header('Cache-Control: no-store, private');
if (!Security::enforceAuthenticatedSession($config)) { Security::destroySession(); header('Location: login.php'); exit; }
try {
    $db=Database::connection();
    $user=Authorization::user($db,(int)$_SESSION['user_id']);
    if (!$user) { Security::destroySession(); header('Location: login.php'); exit; }
    $data=DashboardData::forUser($db,$user);
} catch (Throwable $e) {
    error_log('Dashboard error: '.$e->getMessage()); http_response_code(503);
    exit($config['debug'] ? Security::e($e->getMessage()) : 'Dashboard unavailable. Check database configuration and schema.');
}
$base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\'); if($base==='/')$base='';
function url(string $path=''):string { global $base; return $base.'/'.ltrim($path,'/'); }
$viewMap=['RESIDENT'=>'dashboards/resident','VISITOR'=>'dashboards/visitor','BARANGAY_OFFICIAL'=>'dashboards/official','BARANGAY_ADMINISTRATOR'=>'dashboards/administrator'];
$view=$viewMap[$user['role_code']]??'dashboards/visitor';
View::render($view,['user'=>$user,'data'=>$data,'db'=>$db]);
