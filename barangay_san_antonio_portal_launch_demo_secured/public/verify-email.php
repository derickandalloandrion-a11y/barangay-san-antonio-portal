<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/PublicPage.php';
require dirname(__DIR__).'/app/Core/Database.php';
$message='This verification link is invalid, expired, or has already been used.';
$token=(string)($_GET['token']??'');
if (preg_match('/^[a-f0-9]{64}$/',$token)) {
 try { $db=Database::connection(); $db->beginTransaction();
  $q=$db->prepare("SELECT email_verification_token_id,user_id FROM email_verification_tokens WHERE token_hash=:hash AND used_at IS NULL AND invalidated_at IS NULL AND sent_at IS NOT NULL AND expires_at>NOW() FOR UPDATE");
  $q->execute(['hash'=>hash('sha256',$token)]); $row=$q->fetch(PDO::FETCH_ASSOC);
  if($row){
   $u=$db->prepare("UPDATE users SET account_status='ACTIVE',email_verified_at=NOW() WHERE user_id=:uid AND account_status='PENDING_EMAIL' AND email_verified_at IS NULL");
   $u->execute(['uid'=>$row['user_id']]);
   if($u->rowCount()===1){$t=$db->prepare('UPDATE email_verification_tokens SET used_at=NOW() WHERE email_verification_token_id=:id AND used_at IS NULL');$t->execute(['id'=>$row['email_verification_token_id']]);$db->prepare("UPDATE email_verification_tokens SET invalidated_at=NOW() WHERE user_id=:uid AND email_verification_token_id<>:id AND used_at IS NULL AND invalidated_at IS NULL")->execute(['uid'=>$row['user_id'],'id'=>$row['email_verification_token_id']]);$message='Your email address has been verified. You can now sign in.';}
  }
  $db->commit();
 } catch(Throwable $e){if(isset($db)&&$db instanceof PDO&&$db->inTransaction())$db->rollBack();$message='Email verification is temporarily unavailable. Please try again later.';}
}
pageHeader('Email Verification','Verify your Barangay San Antonio Portal email address.');
?><main id="main-content"><section class="section"><div class="content-layout"><article class="content-panel"><h1>Email Verification</h1><div class="notice info" role="status"><?= Security::e($message) ?></div><a class="action-primary" href="<?= Security::e(url('login.php')) ?>">Continue to sign in</a></article></div></section></main><?php pageFooter(); ?>
