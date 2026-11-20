<?php
declare(strict_types=1);
final class DashboardData
{
    public static function forUser(PDO $db, array $user): array
    {
        $uid=(int)$user['user_id']; $role=(string)$user['role_code'];
        $d=['profile'=>null,'transactions'=>[],'appointments'=>[],'notifications'=>[],'counts'=>[],'queues'=>[],'permissions'=>[],'resident_verification'=>null];
        $p=$db->prepare('SELECT first_name,middle_name,last_name,suffix,mobile_number,address_line,barangay,municipality,province FROM user_profiles WHERE user_id=:uid LIMIT 1');
        $p->execute(['uid'=>$uid]); $d['profile']=$p->fetch(PDO::FETCH_ASSOC)?:null;
        $own=$db->prepare('SELECT transaction_id,reference_code,module_type,current_status,title,details,metadata_json,created_at,updated_at FROM service_transactions WHERE created_by=:uid ORDER BY created_at DESC LIMIT 100');
        $own->execute(['uid'=>$uid]); $d['transactions']=$own->fetchAll(PDO::FETCH_ASSOC);
        $a=$db->prepare('SELECT appointment_id,reference_code,office_unit,appointment_purpose,requested_date,requested_time,status,created_at FROM appointment_requests WHERE user_id=:uid ORDER BY created_at DESC LIMIT 50');
        $a->execute(['uid'=>$uid]); $d['appointments']=$a->fetchAll(PDO::FETCH_ASSOC);
        $rv=$db->prepare('SELECT verification_status,submitted_at,decision_reason FROM resident_verification_requests WHERE user_id=:uid LIMIT 1'); $rv->execute(['uid'=>$uid]); $d['resident_verification']=$rv->fetch(PDO::FETCH_ASSOC)?:null;
        $permMap=['documents'=>'documents.review','reports'=>'incidents.manage','medicine'=>'medicine.manage','appointments'=>'appointments.manage','users'=>'users.manage','roles'=>'roles.manage','audit'=>'audit.view','settings'=>'settings.manage','content'=>'content.manage','finance'=>'transparency.manage'];
        foreach($permMap as $k=>$v)$d['permissions'][$k]=Authorization::can($db,$user,$v);
        $d['counts']=['medicine'=>0,'documents'=>0,'reports'=>0,'appointments'=>count($d['appointments']),'transactions'=>count($d['transactions'])];
        foreach(['DOCUMENT_REQUEST'=>'documents','INCIDENT_REPORT'=>'reports','MEDICINE_REQUEST'=>'medicine'] as $type=>$key){$q=$db->prepare('SELECT COUNT(*) FROM service_transactions WHERE created_by=:u AND module_type=:m');$q->execute(['u'=>$uid,'m'=>$type]);$d['counts'][$key]=(int)$q->fetchColumn();}
        $d['counts']['formal_reports']=$d['counts']['reports']; $d['counts']['informal_reports']=0; $d['counts']['medicine_units']=0;
        foreach($d['transactions'] as $tx){if($tx['module_type']==='MEDICINE_REQUEST' && !empty($tx['metadata_json'])){$meta=json_decode((string)$tx['metadata_json'],true);$qty=$meta['medicine_quantity']??null;if(is_string($qty)&&preg_match('/^\d+$/',$qty))$d['counts']['medicine_units']+=(int)$qty;}}
        $d['notifications']=$d['transactions'];
        $ann=$db->query("SELECT content_id,title,published_at AS created_at,status FROM content_items WHERE status='PUBLISHED' AND content_type IN ('ANNOUNCEMENT','NOTICE') ORDER BY published_at DESC LIMIT 10");
        foreach($ann->fetchAll(PDO::FETCH_ASSOC) as $item){$d['notifications'][]=['reference_code'=>'ANN-'.$item['content_id'],'title'=>$item['title'],'current_status'=>'PUBLISHED','created_at'=>$item['created_at']??''];}
        if(in_array($role,['BARANGAY_OFFICIAL','BARANGAY_ADMINISTRATOR'],true)){
            $filters=[];
            if($role==='BARANGAY_ADMINISTRATOR') $filters=['documents','reports','medicine'];
            else foreach(['documents','reports','medicine'] as $k) if($d['permissions'][$k])$filters[]=$k;
            $types=['documents'=>'DOCUMENT_REQUEST','reports'=>'INCIDENT_REPORT','medicine'=>'MEDICINE_REQUEST'];
            foreach($filters as $k){$q=$db->prepare('SELECT st.transaction_id,st.reference_code,st.module_type,st.current_status,st.title,st.details,st.created_at,st.created_by,u.username,up.first_name,up.last_name FROM service_transactions st JOIN users u ON u.user_id=st.created_by LEFT JOIN user_profiles up ON up.user_id=u.user_id WHERE st.module_type=:m ORDER BY st.created_at DESC LIMIT 100');$q->execute(['m'=>$types[$k]]);$d['queues'][$k]=$q->fetchAll(PDO::FETCH_ASSOC);}
            if($role==='BARANGAY_ADMINISTRATOR'||$d['permissions']['appointments']){$q=$db->query('SELECT a.appointment_id,a.reference_code,a.office_unit,a.appointment_purpose,a.requested_date,a.requested_time,a.status,a.created_at,a.user_id,u.username FROM appointment_requests a JOIN users u ON u.user_id=a.user_id ORDER BY a.created_at DESC LIMIT 100');$d['queues']['appointments']=$q->fetchAll(PDO::FETCH_ASSOC);}
            if($role==='BARANGAY_ADMINISTRATOR'){$q=$db->query('SELECT COUNT(*) FROM users');$d['counts']['all_accounts']=(int)$q->fetchColumn();$q=$db->query('SELECT COUNT(*) FROM service_transactions');$d['counts']['all_transactions']=(int)$q->fetchColumn();$q=$db->query('SELECT COUNT(*) FROM appointment_requests');$d['counts']['all_appointments']=(int)$q->fetchColumn();$q=$db->query('SELECT COUNT(*) FROM audit_logs');$d['counts']['audit_events']=(int)$q->fetchColumn();}
        }
        usort($d['notifications'],static fn($x,$y)=>strcmp((string)$y['created_at'],(string)$x['created_at']));
        $d['notifications']=array_slice($d['notifications'],0,15);
        return $d;
    }
}
