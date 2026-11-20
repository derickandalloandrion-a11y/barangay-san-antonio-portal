<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/config/app.php';
require dirname(__DIR__).'/app/Core/Security.php';
require dirname(__DIR__).'/app/Core/Database.php';
require dirname(__DIR__).'/app/Core/WorkflowHelpers.php';
Security::startSession($config);
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('Method not allowed.');}
if(!Security::verifyCsrf($_POST['_csrf']??null)){http_response_code(419);exit('Your form session expired. Please return and try again.');}
try{
 if(trim((string)($_POST['website']??''))!=='') throw new InvalidArgumentException('Unable to accept this submission.');
 $category=WorkflowHelpers::clean((string)($_POST['category']??''),100);
 $allowed=['Peace and Order','Community Safety','Health and Sanitation','Civil and Administrative','Youth and Social','Gender-based Violence','Environmental Concern','Other'];
 $date=trim((string)($_POST['incident_date']??''));
 $location=WorkflowHelpers::clean((string)($_POST['incident_location']??''),255);
 $details=WorkflowHelpers::clean((string)($_POST['details']??''),5000);
 $contact=WorkflowHelpers::clean((string)($_POST['contact_info']??''),180);
 if(!in_array($category,$allowed,true)||$location===''||$details==='') throw new InvalidArgumentException('Complete all required fields.');
 if($date!==''&&!WorkflowHelpers::validDate($date)) throw new InvalidArgumentException('Enter a valid incident date.');
 if(($_POST['truth_confirmed']??'')!=='1'||($_POST['privacy_acknowledged']??'')!=='1') throw new InvalidArgumentException('Please confirm the report and privacy notice.');
 $db=Database::connection();
 $q=$db->prepare('INSERT INTO anonymous_reports (reference_code,category,incident_date,incident_location,details,contact_info,status,ip_hash) VALUES (:ref,:cat,:date,:loc,:details,:contact,\'SUBMITTED\',:ip)');
 $ref=WorkflowHelpers::reference('ANON');$ip=(string)($_SERVER['REMOTE_ADDR']??'');
 $q->execute(['ref'=>$ref,'cat'=>$category,'date'=>$date!==''?$date:null,'loc'=>$location,'details'=>$details,'contact'=>$contact!==''?$contact:null,'ip'=>$ip!==''?hash('sha256',$ip):null]);
 $_SESSION['anonymous_report_reference']=$ref;
 header('Location: '.(rtrim(dirname($_SERVER['SCRIPT_NAME']),'/\\').'/anonymous_report_received.php'));exit;
}catch(InvalidArgumentException $e){http_response_code(422);$message=$e->getMessage();}catch(Throwable $e){error_log('Anonymous report submission: '.$e->getMessage());http_response_code(503);$message='Unable to submit your report. Please contact the Barangay directly if the matter is urgent.';}
require dirname(__DIR__).'/app/Core/PublicPage.php';pageHeader('Anonymous report not submitted');
?><main id="main-content" class="section"><article class="content-panel"><div class="notice warning" role="alert"><?= Security::e($message??'Submission failed.') ?></div><a class="action-secondary" href="<?= Security::e(url('service.php?type=incident-report&mode=anonymous')) ?>">Return to anonymous report form</a></article></main><?php pageFooter();
