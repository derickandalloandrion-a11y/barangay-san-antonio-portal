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
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit('Method not allowed.'); }
if (!Security::verifyCsrf($_POST['_csrf']??null)) { http_response_code(419); exit('Session token expired.'); }
try {
    $db=Database::connection(); $user=WorkflowHelpers::requireUser($db,$config);
    $kind=(string)($_POST['kind']??''); $id=(int)($_POST['id']??0); $status=(string)($_POST['status']??'');
    $notes=WorkflowHelpers::clean((string)($_POST['notes']??''),1000);
    if ($id<1) throw new InvalidArgumentException('Invalid record reference.');
    $map=[
        'service'=>['table'=>'service_transactions','id'=>'transaction_id','status'=>'current_status','permission'=>['DOCUMENT_REQUEST'=>'documents.review','INCIDENT_REPORT'=>'incidents.manage','MEDICINE_REQUEST'=>'medicine.manage'],'allowed'=>['UNDER_REVIEW','APPROVED','REJECTED','PROCESSING','COMPLETED','CANCELLED'],'history'=>'service_status_history','history_fk'=>'transaction_id','event'=>'SERVICE_REQUEST_STATUS_CHANGED'],
        'appointment'=>['table'=>'appointment_requests','id'=>'appointment_id','status'=>'status','permission'=>['*'=>'appointments.manage'],'allowed'=>['UNDER_REVIEW','CONFIRMED','DECLINED','COMPLETED','CANCELLED'],'history'=>'appointment_status_history','history_fk'=>'appointment_id','event'=>'APPOINTMENT_STATUS_CHANGED'],
        'foi'=>['table'=>'foi_requests','id'=>'foi_request_id','status'=>'status','permission'=>['*'=>'foi.review'],'allowed'=>['UNDER_REVIEW','RELEASED','PARTIALLY_RELEASED','DENIED','CLOSED'],'history'=>'foi_status_history','history_fk'=>'foi_request_id','event'=>'FOI_STATUS_CHANGED'],
        'feedback'=>['table'=>'feedback_submissions','id'=>'feedback_id','status'=>'status','permission'=>['*'=>'feedback.manage'],'allowed'=>['IN_REVIEW','RESOLVED','CLOSED'],'history'=>null,'event'=>'FEEDBACK_STATUS_CHANGED'],
        'resident'=>['table'=>'resident_verification_requests','id'=>'resident_verification_request_id','status'=>'verification_status','permission'=>['*'=>'officials.resident_verification.review'],'allowed'=>['UNDER_REVIEW','APPROVED','REJECTED'],'history'=>null,'event'=>'RESIDENT_VERIFICATION_CHANGED'],
        'data_subject'=>['table'=>'data_subject_requests','id'=>'data_subject_request_id','status'=>'status','permission'=>['*'=>'data.subject.manage'],'allowed'=>['UNDER_REVIEW','APPROVED','DENIED','COMPLETED'],'history'=>null,'event'=>'DATA_SUBJECT_REQUEST_CHANGED'],
    ];
    if (!isset($map[$kind])) throw new InvalidArgumentException('Unknown workflow type.');
    $m=$map[$kind];
    if (!in_array($status,$m['allowed'],true)) throw new InvalidArgumentException('Invalid status transition.');
    if ($kind==='service') {
        $probe=$db->prepare('SELECT module_type FROM service_transactions WHERE transaction_id=:id'); $probe->execute(['id'=>$id]); $module=$probe->fetchColumn();
        if (!$module) throw new InvalidArgumentException('Request not found.');
        $permission=$m['permission'][$module]??null;
    } else $permission=$m['permission']['*'];
    if (!$permission || !Authorization::can($db,$user,$permission)) { http_response_code(403); exit('Your position or role does not grant this review permission.'); }
    $db->beginTransaction();
    $select=$db->prepare('SELECT `'.$m['status'].'` AS old_status FROM `'.$m['table'].'` WHERE `'.$m['id'].'`=:id FOR UPDATE');
    $select->execute(['id'=>$id]); $old=$select->fetchColumn();
    if ($old===false) throw new InvalidArgumentException('Record not found.');
    $transitions = [
        'service'=>['SUBMITTED'=>['UNDER_REVIEW','CANCELLED'],'UNDER_REVIEW'=>['APPROVED','REJECTED','CANCELLED'],'APPROVED'=>['PROCESSING','COMPLETED','CANCELLED'],'PROCESSING'=>['COMPLETED','REJECTED'],'REJECTED'=>[],'COMPLETED'=>[],'CANCELLED'=>[]],
        'appointment'=>['SUBMITTED'=>['UNDER_REVIEW','CONFIRMED','DECLINED','CANCELLED'],'UNDER_REVIEW'=>['CONFIRMED','DECLINED','CANCELLED'],'CONFIRMED'=>['COMPLETED','CANCELLED'],'DECLINED'=>[],'COMPLETED'=>[],'CANCELLED'=>[]],
        'foi'=>['SUBMITTED'=>['UNDER_REVIEW'],'UNDER_REVIEW'=>['RELEASED','PARTIALLY_RELEASED','DENIED','CLOSED'],'RELEASED'=>['CLOSED'],'PARTIALLY_RELEASED'=>['CLOSED'],'DENIED'=>['CLOSED'],'CLOSED'=>[]],
        'feedback'=>['OPEN'=>['IN_REVIEW','CLOSED'],'IN_REVIEW'=>['RESOLVED','CLOSED'],'RESOLVED'=>['CLOSED'],'CLOSED'=>[]],
        'resident'=>['PENDING'=>['UNDER_REVIEW','APPROVED','REJECTED'],'UNDER_REVIEW'=>['APPROVED','REJECTED'],'APPROVED'=>[],'REJECTED'=>[],'CANCELLED'=>[]],
        'data_subject'=>['SUBMITTED'=>['UNDER_REVIEW'],'UNDER_REVIEW'=>['APPROVED','DENIED','COMPLETED'],'APPROVED'=>['COMPLETED'],'DENIED'=>[],'COMPLETED'=>[]],
    ];
    if (!in_array($status, $transitions[$kind][$old] ?? [], true)) throw new InvalidArgumentException('This status transition is not allowed from '.$old.'.');
    if ($kind==='service') {
        $u=$db->prepare('UPDATE service_transactions SET current_status=:status,reviewed_by_user_id=:uid,review_notes=:notes,updated_at=NOW() WHERE transaction_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    } elseif ($kind==='appointment') {
        $u=$db->prepare('UPDATE appointment_requests SET status=:status,reviewed_by_user_id=:uid,review_notes=:notes,updated_at=NOW() WHERE appointment_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    } elseif ($kind==='foi') {
        $u=$db->prepare('UPDATE foi_requests SET status=:status,reviewed_by_user_id=:uid,response_notes=:notes,updated_at=NOW() WHERE foi_request_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    } elseif ($kind==='feedback') {
        $u=$db->prepare('UPDATE feedback_submissions SET status=:status,assigned_to_user_id=:uid,staff_response=:notes,updated_at=NOW() WHERE feedback_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    } elseif ($kind==='resident') {
        $u=$db->prepare('UPDATE resident_verification_requests SET verification_status=:status,reviewed_by_user_id=:uid,reviewed_at=NOW(),decision_reason=:notes,updated_at=NOW() WHERE resident_verification_request_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    } else {
        $u=$db->prepare('UPDATE data_subject_requests SET status=:status,handled_by_user_id=:uid,response_notes=:notes,updated_at=NOW() WHERE data_subject_request_id=:id');
        $u->execute(['status'=>$status,'uid'=>$user['user_id'],'notes'=>$notes,'id'=>$id]);
    }
    if ($m['history']) {
        $h=$db->prepare('INSERT INTO `'.$m['history'].'` (`'.$m['history_fk'].'`,old_status,new_status,notes,changed_by_user_id) VALUES (:id,:old,:new,:notes,:uid)');
        $h->execute(['id'=>$id,'old'=>$old,'new'=>$status,'notes'=>$notes,'uid'=>$user['user_id']]);
    }
    AuditLogger::record($db,(int)$user['user_id'],$m['event'],$kind,$id,['old_status'=>$old,'new_status'=>$status]);
    $db->commit(); header('Location: dashboard.php?updated=1'); exit;
} catch (InvalidArgumentException $e) {
    if (isset($db)&&$db instanceof PDO&&$db->inTransaction()) $db->rollBack(); http_response_code(422); $message=$e->getMessage();
} catch (Throwable $e) {
    if (isset($db)&&$db instanceof PDO&&$db->inTransaction()) $db->rollBack(); error_log('Workflow review error: '.$e->getMessage()); http_response_code(503); $message=$config['debug']?$e->getMessage():'Unable to update the record.';
}
require dirname(__DIR__).'/app/Core/PublicPage.php'; pageHeader('Workflow update failed');
?><main id="main-content" class="section"><article class="content-panel"><div class="notice warning" role="alert"><?= Security::e($message??'Update failed.') ?></div><a class="action-secondary" href="dashboard.php">Return to dashboard</a></article></main><?php pageFooter();
