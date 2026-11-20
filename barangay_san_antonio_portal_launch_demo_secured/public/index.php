<?php
declare(strict_types=1);
$config = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Manila');
require dirname(__DIR__) . '/app/Core/PublicPage.php';
require dirname(__DIR__) . '/app/Core/Database.php';

$notice = null;
if (isset($_GET['dbcheck'])) {
    try {
        require dirname(__DIR__) . '/app/Services/SchemaHealth.php';
        $health = SchemaHealth::inspect(Database::connection());
        $notice = $health;
    } catch (Throwable $e) {
        $notice = ['error' => $config['debug'] ? $e->getMessage() : 'Database connection or configuration is not ready.'];
    }
}
pageHeader('Community Portal', 'Barangay San Antonio community services, documents, announcements and public information.');
?>
<main id="main-content">
<section class="hero" id="community">
  <div class="hero-copy"><p class="eyebrow">WELCOME TO OUR COMMUNITY</p><h1>Looking for someone<br>in our Community?</h1><form class="name-search" action="<?= Security::e(url('search.php')) ?>" method="get"><input name="q" aria-label="Search public information" placeholder="Search officials, services, or information" required><button type="submit">Search</button></form><p class="micro">Find community information and public resources.</p></div>
  <div class="hero-photo"><div class="photo-placeholder"><img src="<?= Security::e(url('assets/images/barangay-seal.png')) ?>" alt="Barangay San Antonio seal"><span>OUR COMMUNITY</span><small>Barangay San Antonio • Candon City</small></div></div>
</section>
<section class="section documents" id="documents"><div class="section-heading"><p class="eyebrow">PUBLIC SERVICES</p><h2>Most Requested Documents</h2></div><div class="document-grid">
  <a class="document-card" href="<?= Security::e(url('service.php?type=barangay-clearance')) ?>"><span class="card-arrow">→</span><h3>Barangay Clearance</h3><p>A Barangay Clearance is an official document issued by your local barangay certifying that you are a resident in good standing.</p><span class="learn">Request document</span></a>
  <a class="document-card" href="<?= Security::e(url('service.php?type=certificate-of-indigency')) ?>"><span class="card-arrow">→</span><h3>Certificate of Indigency</h3><p>A certificate issued by the barangay for eligible residents who need assistance for financial or social welfare purposes.</p><span class="learn">Request document</span></a>
  <a class="document-card" href="<?= Security::e(url('service.php?type=community-tax-certificate')) ?>"><span class="card-arrow">→</span><h3>Community Tax Certificate</h3><p>Information about community tax certification and the process for obtaining local documentation.</p><span class="learn">Request document</span></a>
</div></section>
<section class="ad-section"><span>ADVERTISEMENT</span><div class="ad-frame">Your advertisement space</div><a href="<?= Security::e(url('information.php?section=advertising')) ?>">ADVERTISING POLICY</a></section>
<section class="volunteer"><div class="paper-icon">▤</div><div><h2>Want to be a Volunteer?</h2><p>Be one of us by simply<br>filling up a form.</p></div><a class="pill-button" href="<?= Security::e(url('register.php')) ?>">SIGN UP HERE</a></section>
<section class="hymn" id="about"><div class="hymn-copy"><p class="eyebrow">OUR IDENTITY</p><h2>San Antonio a Napoanan Gasat</h2><p>The official Hymn of Barangay San Antonio, Candon City, Ilocos Sur</p></div><div class="video-placeholder"><img src="<?= Security::e(url('assets/images/barangay-seal.png')) ?>" alt="Barangay San Antonio seal"><span class="play" aria-hidden="true">▶</span><strong>Barangay hymn video</strong><small>Video presentation area • media to be added</small></div></section>
<section class="ad-section second-ad"><span>ADVERTISEMENT</span><div class="ad-frame">Your advertisement space</div><a href="<?= Security::e(url('information.php?section=advertising')) ?>">ADVERTISING POLICY</a></section>
<section class="section articles" id="articles"><div class="section-heading"><p class="eyebrow">COMMUNITY UPDATES</p><h2>Latest news and articles</h2></div><div class="article-grid">
<?php
$articles = [
 ['Community activities','Barangay programs, health and wellness updates.','Community','community-activities'],
 ['Health and wellness','Local health initiatives and community outreach.','Community','health-and-wellness'],
 ['Culture and tradition','Celebrations, heritage and barangay events.','Culture','culture-and-tradition'],
 ['Public announcements','Important notices and service updates.','Announcements','public-announcements'],
 ['Community stories','Highlights from residents and local groups.','Community','community-stories'],
 ['Other articles','More stories from Barangay San Antonio.','Other Articles','other-articles'],
 ['Understanding barangay services','A guide to online community service requests.','Services','barangay-services'],
 ['Community safety reminders','Practical reminders for a safer community.','Community','community-safety'],
 ['Working together for San Antonio','Community participation and local initiatives.','Community','community-activities'],
];
foreach ($articles as $i=>$a): ?>
<article class="article-card<?= $i>=6?' article-extra':'' ?>"<?= $i>=6?' hidden':'' ?>><a class="article-image tone-<?= $i%3 ?>" href="<?= Security::e(url('article.php?slug='.$a[3])) ?>"><img src="<?= Security::e(url('assets/images/barangay-seal.png')) ?>" alt="Barangay San Antonio seal"><span><?= Security::e($a[0]) ?></span></a><div class="article-body"><h3><a href="<?= Security::e(url('article.php?slug='.$a[3])) ?>"><?= Security::e($a[0]) ?></a></h3><p><?= Security::e($a[1]) ?></p><a href="<?= Security::e(url('article.php?slug='.$a[3])) ?>">Read article →</a></div></article>
<?php endforeach; ?>
</div><button class="outline-button" type="button" id="view-more-articles" aria-expanded="false">View More Articles</button></section>
<section class="section" id="public-information"><div class="section-heading"><p class="eyebrow">OPEN GOVERNMENT</p><h2>Public Information &amp; Citizen Services</h2><p>Access public records, service standards, and channels for community participation.</p></div><div class="civic-grid"><a class="civic-card" href="<?= Security::e(url('transparency.php')) ?>"><h3>Transparency &amp; Open Data</h3><p>Budgets, procurement notices, annual reports, and published datasets.</p><span class="learn">Explore public records →</span></a><a class="civic-card" href="<?= Security::e(url('citizens-charter.php')) ?>"><h3>Citizen’s Charter</h3><p>Review service requirements, fees, and processing information.</p><span class="learn">View service standards →</span></a><a class="civic-card" href="<?= Security::e(url('foi.php')) ?>"><h3>Freedom of Information</h3><p>Submit a request for access to public records.</p><span class="learn">Make an FOI request →</span></a><a class="civic-card" href="<?= Security::e(url('appointment.php')) ?>"><h3>Appointments</h3><p>Request a preferred schedule with the Barangay office.</p><span class="learn">Request an appointment →</span></a><a class="civic-card" href="<?= Security::e(url('feedback.php')) ?>"><h3>Feedback &amp; Complaints</h3><p>Share your experience and help improve services.</p><span class="learn">Send feedback →</span></a><a class="civic-card" href="<?= Security::e(url('privacy.php')) ?>"><h3>Privacy &amp; Data Protection</h3><p>Read the draft privacy notice and submit a data subject request.</p><span class="learn">Read privacy notice →</span></a></div></section>
<section class="slogan"><h2>FOR BETTER SAN ANTONIO</h2><p>2023 – 2026</p></section>
</main>
<?php if ($notice): ?><aside class="db-notice"><b>Database diagnostic</b><pre><?= Security::e(json_encode($notice, JSON_PRETTY_PRINT)) ?></pre></aside><?php endif; ?>
<?php pageFooter(); ?>
