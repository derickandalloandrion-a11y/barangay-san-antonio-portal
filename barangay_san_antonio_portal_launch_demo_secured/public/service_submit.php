<?php
declare(strict_types=1);
$config=require dirname(__DIR__).'/config/app.php';
require dirname(__DIR__).'/app/Core/Security.php';
require dirname(__DIR__).'/app/Core/Database.php';
require dirname(__DIR__).'/app/Core/SchemaInspector.php';
require dirname(__DIR__).'/app/Core/AuditLogger.php';
require dirname(__DIR__).'/app/Core/WorkflowHelpers.php';
require dirname(__DIR__).'/app/Services/Authorization.php';
Security::startSession($config);
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); header('Allow: POST'); exit('Method not allowed.'); }
if (!Security::verifyCsrf($_POST['_csrf']??null)) { http_response_code(419); exit('Your form session expired. Refresh and try again.'); }
try {
    $db=Database::connection(); $user=WorkflowHelpers::requireUser($db,$config);
    $type=strtoupper(trim((string)($_POST['module_type']??'')));
    $permission=['DOCUMENT_REQUEST'=>'documents.create','INCIDENT_REPORT'=>'incidents.create','MEDICINE_REQUEST'=>'medicine.create'];
    if (!isset($permission[$type]) || !Authorization::can($db,$user,$permission[$type])) throw new InvalidArgumentException('Your account does not have permission to submit this service request.');
    if ($type==='DOCUMENT_REQUEST' || $type==='MEDICINE_REQUEST') {
        if ($user['role_code']!=='RESIDENT') throw new InvalidArgumentException('This service is available to verified residents only.');
        $v=$db->prepare("SELECT verification_status FROM resident_verification_requests WHERE user_id=:uid LIMIT 1");
        $v->execute(['uid'=>$user['user_id']]);
        if ($v->fetchColumn()!=='APPROVED') throw new InvalidArgumentException('Your resident verification must be approved before requesting documents or medicine assistance.');
    }
    if (!in_array($user['role_code'],['RESIDENT','VISITOR'],true)) throw new InvalidArgumentException('Staff accounts cannot submit citizen service requests.');
    $title=WorkflowHelpers::clean((string)($_POST['title']??''),180);
    $details=WorkflowHelpers::clean((string)($_POST['details']??''),5000);
    if ($title==='' || $details==='') throw new InvalidArgumentException('Complete the subject and details.');
    $extra=[];
    if ($type==='DOCUMENT_REQUEST') {
        $doc=trim((string)($_POST['document_type']??''));
        if (!in_array($doc,['Barangay Clearance','Certificate of Indigency','Community Tax Certificate','Certificate of Residency','Certificate of Employment','Barangay Business Permit','Barangay Business Endorsement','Certification for School','Certification for Scholarship','Certification for Legal Purposes','Certification for Employment','Certification to File Action'],true)) throw new InvalidArgumentException('Choose a valid document type.');
        $purpose=WorkflowHelpers::clean((string)($_POST['purpose']??''),100);
        $allowedPurposes=['Employment','School / Education','Business','Legal purposes','Financial assistance','Personal / Other'];
        if ($purpose!=='' && !in_array($purpose,$allowedPurposes,true)) throw new InvalidArgumentException('Choose a valid purpose.');
        $extra=['document_type'=>$doc,'purpose'=>$purpose];
    } elseif ($type==='INCIDENT_REPORT') {
        $date=trim((string)($_POST['incident_date']??'')); $loc=WorkflowHelpers::clean((string)($_POST['incident_location']??''),255);
        if ($date!=='' && !WorkflowHelpers::validDate($date)) throw new InvalidArgumentException('Enter a valid incident date.');
        $category=WorkflowHelpers::clean((string)($_POST['incident_category']??''),100);
        $allowedCategories=['Peace and Order','Community Safety','Health and Sanitation','Civil and Administrative','Youth and Social','Gender-based Violence','Environmental Concern','Other'];
        if (!in_array($category,$allowedCategories,true)) throw new InvalidArgumentException('Choose a valid incident category.');
        $extra=['incident_category'=>$category,'incident_date'=>$date,'incident_location'=>$loc];
    } else {
        $name=WorkflowHelpers::clean((string)($_POST['medicine_name']??''),180);
        $prescription=trim((string)($_POST['prescription_available']??'NO'));
        if ($name==='' || !in_array($prescription,['YES','NO'],true)) throw new InvalidArgumentException('Enter the medicine name and prescription availability.');
        $strength=WorkflowHelpers::clean((string)($_POST['medicine_strength']??''),100);
        $quantity=WorkflowHelpers::clean((string)($_POST['medicine_quantity']??''),60);
        $notes=WorkflowHelpers::clean((string)($_POST['medicine_notes']??''),1500);
        $extra=['medicine_name'=>$name,'medicine_strength'=>$strength,'medicine_quantity'=>$quantity,'prescription_available'=>$prescription,'medicine_notes'=>$notes];
    }
    if (($_POST['information_confirmed']??'')!=='1' || ($_POST['privacy_acknowledged']??'')!=='1') throw new InvalidArgumentException('Please confirm the information accuracy and privacy notice before submitting.');
    $reference=WorkflowHelpers::reference($type==='DOCUMENT_REQUEST'?'DOC':($type==='INCIDENT_REPORT'?'INC':'MED'));
    $db->beginTransaction();
    $q=$db->prepare("INSERT INTO service_transactions (reference_code,created_by,module_type,current_status,title,details,metadata_json) VALUES (:ref,:uid,:type,'SUBMITTED',:title,:details,:meta)");
    $q->execute(['ref'=>$reference,'uid'=>$user['user_id'],'type'=>$type,'title'=>$title,'details'=>$details,'meta'=>json_encode($extra,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    $id=(int)$db->lastInsertId();
    $h=$db->prepare("INSERT INTO service_status_history (transaction_id,old_status,new_status,notes,changed_by_user_id) VALUES (:id,NULL,'SUBMITTED','Initial submission',:uid)");
    $h->execute(['id'=>$id,'uid'=>$user['user_id']]);
    AuditLogger::record($db,(int)$user['user_id'],'SERVICE_REQUEST_SUBMITTED','service_transaction',$id,['module_type'=>$type,'reference_code'=>$reference]);
    $db->commit();
    header('Location: dashboard.php?submitted=1'); exit;
} catch (InvalidArgumentException $e) {
    if (isset($db)&&$db instanceof PDO&&$db->inTransaction()) $db->rollBack();
    http_response_code(422); $message=$e->getMessage();
} catch (Throwable $e) {
    if (isset($db)&&$db instanceof PDO&&$db->inTransaction()) $db->rollBack();
    error_log('Service submission error: '.$e->getMessage()); http_response_code(503); $message=$config['debug']?$e->getMessage():'Unable to submit this request. Please check the database setup.';
}
require dirname(__DIR__).'/app/Core/PublicPage.php'; pageHeader('Request not submitted');
?><main id="main-content" class="section"><article class="content-panel"><div class="notice warning" role="alert"><?= Security::e($message??'Request not submitted.') ?></div><a class="action-secondary" href="javascript:history.back()">Return to form</a></article></main><?php pageFooter();
