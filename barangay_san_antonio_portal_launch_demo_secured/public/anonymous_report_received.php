<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/Core/PublicPage.php';
$reference=(string)($_SESSION['anonymous_report_reference']??'');unset($_SESSION['anonymous_report_reference']);
pageHeader('Anonymous report received','Confirmation of anonymous report submission');
?><main id="main-content" class="section"><article class="content-panel"><h1>Thank you for your report</h1><p>Your anonymous report has been received.</p><?php if($reference!==''): ?><p><strong>Reference number: <?= Security::e($reference) ?></strong></p><p>Please keep this reference number for your records. Because the report is anonymous, it cannot be linked to a personal dashboard.</p><?php endif; ?><a class="action-primary" href="<?= Security::e(url('service.php?type=incident-report')) ?>">Return to File a Report</a></article></main><?php pageFooter();
