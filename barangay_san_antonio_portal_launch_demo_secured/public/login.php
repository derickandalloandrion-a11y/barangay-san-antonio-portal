<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/config/app.php';
require dirname(__DIR__).'/app/Core/Security.php';require dirname(__DIR__).'/app/Core/Database.php';require dirname(__DIR__).'/app/Core/View.php';require dirname(__DIR__).'/app/Core/SchemaInspector.php';require dirname(__DIR__).'/app/Core/AuditLogger.php';require dirname(__DIR__).'/app/Core/RateLimiter.php';require dirname(__DIR__).'/app/Services/Authorization.php';
Security::sendSecurityHeaders($config);
header('Cache-Control: no-store, private');
Security::startSession($config);if(!empty($_SESSION['user_id'])){header('Location: dashboard.php');exit;}
$account=strtolower((string)($_GET['account']??''));if(!in_array($account,['official','resident','visitor'],true))$account='';
$accountLabel=['official'=>'Barangay Functionary','resident'=>'Community Member','visitor'=>'Visitor'][$account]??'your account';$error=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!Security::verifyCsrf($_POST['_csrf']??null)){http_response_code(419);$error='Your session token expired. Refresh the page and try again.';}
 else{$identity=trim((string)($_POST['identity']??''));$password=(string)($_POST['password']??'');$ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');if(!RateLimiter::allow('login',$ip,30,900)){$error='Too many sign-in attempts from this network. Please wait 15 minutes and try again.';}elseif($identity===''||$password==='')$error='Enter your username/email and password.';else{
  try{$db=Database::connection();$schema=new SchemaInspector($db);if(!$schema->hasColumns('users',['user_id','username','email','password_hash','account_status','email_verified_at','account_type','failed_login_count','locked_until','last_login_at']))throw new RuntimeException('The users table is missing required login security columns.');
   $db->beginTransaction();$q=$db->prepare('SELECT user_id,username,email,password_hash,account_status,email_verified_at,account_type,failed_login_count,locked_until,(locked_until IS NOT NULL AND locked_until>NOW()) AS is_locked,(locked_until IS NOT NULL AND locked_until<=NOW()) AS lock_expired FROM users WHERE email=:email OR username=:username LIMIT 1 FOR UPDATE');$q->execute(['email'=>$identity,'username'=>$identity]);$user=$q->fetch(PDO::FETCH_ASSOC);
   if(!$user){$db->rollBack();$error='Invalid sign-in credentials.';}
   elseif((int)$user['is_locked']===1){$db->rollBack();$error='Invalid sign-in credentials. If your account is temporarily locked, wait 15 minutes before trying again.';}
   elseif(!password_verify($password,$user['password_hash'])){$count=(int)$user['failed_login_count'];if((int)$user['lock_expired']===1)$count=0;$count++;$locked=$count>=5;$u=$db->prepare('UPDATE users SET failed_login_count=:count,locked_until=CASE WHEN :locked=1 THEN DATE_ADD(NOW(),INTERVAL 15 MINUTE) ELSE NULL END WHERE user_id=:uid');$u->execute(['count'=>$count,'locked'=>$locked?1:0,'uid'=>$user['user_id']]);$db->commit();AuditLogger::record($db,(int)$user['user_id'],'LOGIN_FAILED','user',(int)$user['user_id']);$error='Invalid sign-in credentials. If your account is temporarily locked, wait 15 minutes before trying again.';}
   elseif($account==='official'&&!in_array($user['account_type'],['OFFICIAL','ADMINISTRATOR'],true)){$db->rollBack();$error='This sign-in page is for Barangay Functionary accounts. Choose the correct account type.';}
   elseif($account==='resident'&&$user['account_type']!=='RESIDENT'){$db->rollBack();$error='This sign-in page is for Community Member accounts. Choose the correct account type.';}
   elseif($account==='visitor'&&$user['account_type']!=='VISITOR'){$db->rollBack();$error='This sign-in page is for Visitor accounts. Choose the correct account type.';}
   elseif($user['account_status']!=='ACTIVE'||empty($user['email_verified_at'])){$db->rollBack();$error='Your account is not active or your email has not been verified.';}
   else{$authorized=Authorization::user($db,(int)$user['user_id']);if(!$authorized){$db->rollBack();$error='No single valid active role is assigned to this account. Contact the barangay administrator.';}else{$db->prepare('UPDATE users SET failed_login_count=0,locked_until=NULL,last_login_at=NOW() WHERE user_id=:uid')->execute(['uid'=>$user['user_id']]);$db->commit();AuditLogger::record($db,(int)$user['user_id'],'LOGIN_SUCCEEDED','user',(int)$user['user_id']);Security::establishAuthenticatedSession((int)$user['user_id']);header('Location: dashboard.php');exit;}}
  }catch(Throwable $e){if(isset($db)&&$db instanceof PDO&&$db->inTransaction())$db->rollBack();error_log('Login failure: '.$e->getMessage());$error=$config['debug']?$e->getMessage():'Sign-in is temporarily unavailable. Check the database configuration and schema.';}
 }}
}
$base=rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\');if($base==='/')$base='';function url(string $path=''):string{global $base;return $base.'/'.ltrim($path,'/');}
View::render('login',['error'=>$error,'accountLabel'=>$accountLabel,'account'=>$account]);
