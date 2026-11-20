<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/PublicPage.php';
require dirname(__DIR__).'/app/Core/Database.php';
$group = (($_GET['group'] ?? 'barangay') === 'sk') ? 'sk' : 'barangay';
$isSk = $group === 'sk';
$role = (string)($_GET['role'] ?? '');
$filters = [
 'captain'=>['Barangay Captain',['captain']], 'council'=>['Council Members',['council','kagawad']],
 'secretary'=>['Barangay Secretary',['secretary']], 'treasurer'=>['Barangay Treasurer',['treasurer']],
 'appointees'=>['Barangay Appointees',['appointee']], 'chairperson'=>['SK Chairperson',['chairperson','chairman']],
 'members'=>['SK Members',['sk member','sk kagawad']]
];
if ($isSk && !in_array($role,['chairperson','members'],true)) $role='';
if (!$isSk && in_array($role,['chairperson','members'],true)) $role='';
$title = $isSk ? 'Sangguniang Kabataan Officials' : 'Barangay Officials';
$filterLabel = $filters[$role][0] ?? '';
if ($filterLabel !== '') $title = $filterLabel;
$rows=[]; $error=null;
try {
 $db=Database::connection();
 $where = $isSk ? "op.position_name LIKE '%SK%'" : "op.position_name NOT LIKE '%SK%'";
 $params=[];
 if ($filterLabel !== '') {
   $parts=[];
   foreach ($filters[$role][1] as $i=>$term) { $key='term'.$i; $parts[]="LOWER(op.position_name) LIKE :$key"; $params[$key]='%'.strtolower($term).'%'; }
   $where .= ' AND ('.implode(' OR ',$parts).')';
 }
 $sql="SELECT up.first_name,up.middle_name,up.last_name,up.suffix,op.position_name FROM official_assignments oa JOIN official_positions op ON op.position_id=oa.position_id JOIN user_profiles up ON up.user_id=oa.user_id JOIN users u ON u.user_id=oa.user_id WHERE oa.assignment_status='ACTIVE' AND oa.verification_status='VERIFIED' AND oa.term_start<=CURDATE() AND (oa.term_end IS NULL OR oa.term_end>=CURDATE()) AND op.is_active=1 AND u.account_status='ACTIVE' AND u.email_verified_at IS NOT NULL AND $where ORDER BY op.position_name,up.last_name,up.first_name";
 $q=$db->prepare($sql); $q->execute($params); $rows=$q->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e) { $error='The officials directory is temporarily unavailable. Please try again later.'; error_log('Officials directory: '.$e->getMessage()); }
pageHeader($title,'Official directory for Barangay San Antonio.');
?>
<main id="main-content"><section class="page-hero"><div class="page-hero-inner"><div class="breadcrumb"><a href="<?= Security::e(url()) ?>">Home</a> / <a href="<?= Security::e(url('directory.php?group='.$group)) ?>">Officials</a> / <?= Security::e($title) ?></div><p class="eyebrow">PUBLIC DIRECTORY</p><h1><?= Security::e($title) ?></h1><p>Meet the currently published officials serving the community.</p><div class="directory-filter-links"><a class="<?= $role===''?'active':'' ?>" href="<?= Security::e(url('directory.php?group='.$group)) ?>">All <?= $isSk?'SK':'Barangay' ?> Officials</a><?php if($isSk):?><a class="<?= $role==='chairperson'?'active':'' ?>" href="<?= Security::e(url('directory.php?group=sk&role=chairperson')) ?>">Chairperson</a><a class="<?= $role==='members'?'active':'' ?>" href="<?= Security::e(url('directory.php?group=sk&role=members')) ?>">Members</a><?php else: foreach(['captain'=>'Captain','council'=>'Council','secretary'=>'Secretary','treasurer'=>'Treasurer','appointees'=>'Appointees'] as $slug=>$label):?><a class="<?= $role===$slug?'active':'' ?>" href="<?= Security::e(url('directory.php?group=barangay&role='.$slug)) ?>"><?= Security::e($label) ?></a><?php endforeach; endif;?></div></div></section><section class="section"><div class="official-directory-grid"><?php if($error):?><div class="notice warning"><?= Security::e($error) ?></div><?php elseif(!$rows):?><div class="notice info directory-empty"><b>No published profiles yet.</b><p>No active <?= Security::e(strtolower($title)) ?> are currently available for this selection. Please check back after the barangay administrator publishes the official assignments.</p><a class="action-secondary" href="<?= Security::e(url('directory.php?group='.$group)) ?>">View all <?= $isSk?'SK':'Barangay' ?> officials</a></div><?php else:foreach($rows as $r):$name=trim($r['first_name'].' '.($r['middle_name']??'').' '.$r['last_name'].' '.($r['suffix']??''));?><article class="directory-card"><div class="directory-avatar" aria-hidden="true"><?= Security::e(strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1))) ?></div><h2><?= Security::e($name) ?></h2><p><?= Security::e($r['position_name']) ?></p></article><?php endforeach;endif;?></div></section></main><?php pageFooter(); ?>
