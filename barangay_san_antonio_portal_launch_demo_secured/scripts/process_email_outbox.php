<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Core/Database.php';
$mailFile=dirname(__DIR__).'/config/mail.php';
if(!is_file($mailFile)){fwrite(STDERR,"Missing config/mail.php.\n");exit(2);}
$cfg=require $mailFile; $driver=(string)($cfg['driver']??'file');
function smtpReadLine($fp): string { $out=''; while(($line=fgets($fp,515))!==false){$out.=$line;if(strlen($line)<4||$line[3]===' ')break;}return $out; }
function smtpExpectCode($fp,array $codes): string { $response=smtpReadLine($fp);$code=(int)substr($response,0,3);if(!in_array($code,$codes,true))throw new RuntimeException('SMTP response: '.trim($response));return $response; }
function smtpSend(array $cfg,string $to,string $subject,string $body): void {
 $s=$cfg['smtp']??[];$host=(string)($s['host']??'');$port=(int)($s['port']??587);$username=(string)($s['username']??'');$password=(string)($s['password']??'');$from=(string)($cfg['from_email']??$username);$name=(string)($cfg['from_name']??'Barangay San Antonio Portal');$timeout=max(5,(int)($s['timeout']??20));
 if($host===''||$username===''||$password===''||!filter_var($from,FILTER_VALIDATE_EMAIL)||!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Configure SMTP host, Gmail address, App Password, and sender email.');
 $fp=stream_socket_client('tcp://'.$host.':'.$port,$errno,$errstr,$timeout,STREAM_CLIENT_CONNECT);if(!$fp)throw new RuntimeException('SMTP connection failed: '.$errstr.' ('.$errno.')');stream_set_timeout($fp,$timeout);
 try {smtpExpectCode($fp,[220]);fwrite($fp,"EHLO localhost\r\n");smtpExpectCode($fp,[250]);fwrite($fp,"STARTTLS\r\n");smtpExpectCode($fp,[220]);if(!stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('SMTP TLS negotiation failed.');fwrite($fp,"EHLO localhost\r\n");smtpExpectCode($fp,[250]);fwrite($fp,"AUTH LOGIN\r\n");smtpExpectCode($fp,[334]);fwrite($fp,base64_encode($username)."\r\n");smtpExpectCode($fp,[334]);fwrite($fp,base64_encode($password)."\r\n");smtpExpectCode($fp,[235]);fwrite($fp,'MAIL FROM:<'.$from.">\r\n");smtpExpectCode($fp,[250]);fwrite($fp,'RCPT TO:<'.$to.">\r\n");smtpExpectCode($fp,[250,251]);fwrite($fp,"DATA\r\n");smtpExpectCode($fp,[354]);
 $headers='From: '.sprintf('"%s" <%s>',addcslashes($name,'"\\'),$from)."\r\nTo: <{$to}>\r\nSubject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";
 $message=$headers."\r\n".str_replace("\n.","\n..",str_replace("\r\n","\n",$body));$message=str_replace("\n","\r\n",$message);fwrite($fp,$message."\r\n.\r\n");smtpExpectCode($fp,[250]);fwrite($fp,"QUIT\r\n");smtpExpectCode($fp,[221]);
 } finally {fclose($fp);}
}
$db=Database::connection();$processed=0;
while($processed<20){
 $db->beginTransaction();
 try{$q=$db->query("SELECT email_outbox_id,user_id,recipient_email,subject,payload_json,attempt_count FROM email_outbox WHERE delivery_status IN ('PENDING','RETRY_SCHEDULED') AND (next_attempt_at IS NULL OR next_attempt_at<=NOW()) ORDER BY email_outbox_id LIMIT 1 FOR UPDATE");$row=$q->fetch(PDO::FETCH_ASSOC);if(!$row){$db->commit();break;}$db->prepare("UPDATE email_outbox SET delivery_status='PROCESSING',attempt_count=attempt_count+1 WHERE email_outbox_id=:id")->execute(['id'=>$row['email_outbox_id']]);$db->commit();}
 catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 $processed++;$payload=json_decode((string)$row['payload_json'],true);$link=is_array($payload)?(string)($payload['verification_url']??''):'';$first=is_array($payload)?(string)($payload['first_name']??'there'):'there';
 $body="Hello {$first},\n\nPlease verify your Barangay San Antonio Portal email address using this link:\n{$link}\n\nThe link expires 15 minutes after successful delivery acceptance. If you did not create this account, ignore this message.\n";
 try{
  if(!filter_var($link,FILTER_VALIDATE_URL))throw new RuntimeException('Verification URL is invalid.');
  if($driver==='file'){
   $dir=(string)($cfg['preview_directory']??(dirname(__DIR__).'/storage/mail-preview'));if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Mail preview directory cannot be created.');
   $path=rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'verification-'.$row['email_outbox_id'].'.txt';$content="LOCAL EMAIL PREVIEW - NOT SENT TO THE INTERNET\nTo: {$row['recipient_email']}\nSubject: {$row['subject']}\n\n{$body}";if(file_put_contents($path,$content,LOCK_EX)===false)throw new RuntimeException('Unable to write local email preview.');@chmod($path,0600);
  } elseif($driver==='smtp') { smtpSend($cfg,$row['recipient_email'],$row['subject'],$body); }
  else throw new RuntimeException('Unsupported mail transport driver.');
  $db->beginTransaction();$db->prepare("UPDATE email_outbox SET delivery_status='SENT',accepted_by_smtp_at=:smtp,sent_at=NOW(),next_attempt_at=NULL,last_error=NULL WHERE email_outbox_id=:id")->execute(['smtp'=>$driver==='smtp'?date('Y-m-d H:i:s'):null,'id'=>$row['email_outbox_id']]);$db->prepare('UPDATE email_verification_tokens SET sent_at=NOW(),expires_at=DATE_ADD(NOW(),INTERVAL 15 MINUTE) WHERE user_id=:uid AND sent_at IS NULL AND used_at IS NULL AND invalidated_at IS NULL')->execute(['uid'=>$row['user_id']]);$db->commit();
 }catch(Throwable $e){$attempt=(int)$row['attempt_count']+1;$delays=[60,300,900];$db->prepare("UPDATE email_outbox SET delivery_status=:status,next_attempt_at=:next,last_error=:err WHERE email_outbox_id=:id")->execute(['status'=>$attempt>=4?'FAILED':'RETRY_SCHEDULED','next'=>$attempt>=4?null:date('Y-m-d H:i:s',time()+$delays[min($attempt-1,2)]),'err'=>substr($e->getMessage(),0,950),'id'=>$row['email_outbox_id']]);fwrite(STDERR,'Mail delivery failed: '.$e->getMessage()."\n");}
}
echo "Processed {$processed} outbox item(s) using {$driver} transport.\n";
if($driver==='file')echo "Local verification email previews are in storage/mail-preview/. These previews are not real email delivery.\n";
