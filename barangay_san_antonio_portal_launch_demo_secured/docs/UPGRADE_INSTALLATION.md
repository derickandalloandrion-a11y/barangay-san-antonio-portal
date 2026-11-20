# Installation

Follow the installation sequence in the project `README.md`. The only schema file in this release is `database/schema_launch_demo.sql`. Import it into a new database named `barangay_san_antonio_portal_launch_demo`. Do not import it into a protected existing database.

Copy `config/database.example.php` to `config/database.php` and set the local MariaDB credentials. `config/mail.php` defaults to local file-preview transport so registration can be tested without Gmail SMTP. The local URL is configured for `http://localhost/barangay_san_antonio_portal_launch_demo/public`.
