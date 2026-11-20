<?php
declare(strict_types=1);

final class RegistrationService
{
    public static function register(PDO $db, array $input, string $ip): int
    {
        $type = strtoupper(trim((string)($input['account_type'] ?? '')));
        if (!in_array($type, ['RESIDENT', 'VISITOR'], true)) {
            throw new InvalidArgumentException('Choose Resident or Visitor account type.');
        }
        $first = trim((string)($input['first_name'] ?? ''));
        $middle = trim((string)($input['middle_name'] ?? ''));
        $last = trim((string)($input['last_name'] ?? ''));
        $suffix = trim((string)($input['suffix'] ?? ''));
        $username = trim((string)($input['username'] ?? ''));
        $email = strtolower(trim((string)($input['email'] ?? '')));
        $password = (string)($input['password'] ?? '');
        $confirm = (string)($input['confirm_password'] ?? '');
        $dob = trim((string)($input['date_of_birth'] ?? ''));
        $mobile = self::normalizePhilippineMobile((string)($input['mobile_number'] ?? ''));
        $address = trim((string)($input['address_line'] ?? ''));
        $barangay = trim((string)($input['barangay'] ?? ''));
        $municipality = trim((string)($input['municipality'] ?? ''));
        $province = trim((string)($input['province'] ?? ''));

        $limits = ['first_name'=>100,'middle_name'=>100,'last_name'=>100,'suffix'=>30,'address_line'=>255,'barangay'=>120,'municipality'=>120,'province'=>120];
        foreach ($limits as $field=>$max) {
            $value = (string)($input[$field] ?? '');
            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($length > $max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) throw new InvalidArgumentException('One of the profile fields contains invalid characters or exceeds its allowed length.');
        }
        if ($first === '' || $last === '') throw new InvalidArgumentException('First name and last name are required.');
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9._]{2,28}[A-Za-z0-9])$/', $username) || preg_match('/[._]{2}/', $username)) {
            throw new InvalidArgumentException('Username must be 4–30 characters, start and end with a letter or number, and use only letters, numbers, periods, or underscores without consecutive punctuation.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) throw new InvalidArgumentException('Enter a valid email address.');
        if (strlen($password) < 12 || strlen($password) > 200 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) throw new InvalidArgumentException('Password must be 12–200 characters and include uppercase, lowercase, a number, and a symbol.');
        if ($password !== $confirm) throw new InvalidArgumentException('The password confirmation does not match.');
        if ($type === 'RESIDENT') {
            if ($dob === '' || $mobile === null || $address === '' || $barangay === '' || $municipality === '' || $province === '') {
                throw new InvalidArgumentException('Residents must complete date of birth, mobile number, street/address, barangay, municipality/city, and province.');
            }
            $date = DateTime::createFromFormat('!Y-m-d', $dob);
            if (!$date || $date->format('Y-m-d') !== $dob || $date >= new DateTime('today')) throw new InvalidArgumentException('Enter a valid date of birth in the past.');
            if ($mobile === null) throw new InvalidArgumentException('Enter a valid Philippine mobile number.');
        }
        if ($type === 'VISITOR') {
            if ($dob !== '') { $date = DateTime::createFromFormat('!Y-m-d', $dob); if (!$date || $date->format('Y-m-d') !== $dob || $date >= new DateTime('today')) throw new InvalidArgumentException('Enter a valid date of birth in the past.'); }
            if (trim((string)($input['mobile_number'] ?? '')) !== '' && $mobile === null) throw new InvalidArgumentException('Enter a valid Philippine mobile number.') ;
            $dob = $dob !== '' ? $dob : null;
            $mobile = $mobile !== '' ? $mobile : null;
            $address = $address !== '' ? $address : null;
            $barangay = $barangay !== '' ? $barangay : null;
            $municipality = $municipality !== '' ? $municipality : null;
            $province = $province !== '' ? $province : null;
        }
        if ($middle === '') $middle = null;
        if ($suffix === '') $suffix = null;

        self::enforceRateLimit($ip, $email);
        $roleCode = $type;
        $role = $db->prepare('SELECT role_id FROM roles WHERE role_code = :code LIMIT 1');
        $role->execute(['code' => $roleCode]);
        $roleId = $role->fetchColumn();
        if (!$roleId) throw new RuntimeException('The required public account role is missing. Import the approved role seed data before enabling registration.');

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $db->beginTransaction();
        try {
            $q = $db->prepare("INSERT INTO users (username,email,password_hash,account_type,account_status,password_changed_at) VALUES (:username,:email,:hash,:type,'PENDING_EMAIL',NOW())");
            $q->execute(['username'=>$username,'email'=>$email,'hash'=>password_hash($password, PASSWORD_DEFAULT),'type'=>$type]);
            $userId = (int)$db->lastInsertId();
            $p = $db->prepare('INSERT INTO user_profiles (user_id,first_name,middle_name,last_name,suffix,date_of_birth,mobile_number,address_line,barangay,municipality,province) VALUES (:uid,:first,:middle,:last,:suffix,:dob,:mobile,:address,:barangay,:municipality,:province)');
            $p->execute(['uid'=>$userId,'first'=>$first,'middle'=>$middle,'last'=>$last,'suffix'=>$suffix,'dob'=>$dob,'mobile'=>$mobile,'address'=>$address,'barangay'=>$barangay,'municipality'=>$municipality,'province'=>$province]);
            $ur = $db->prepare('INSERT INTO user_roles (user_id,role_id,assigned_by_user_id) VALUES (:uid,:rid,NULL)');
            $ur->execute(['uid'=>$userId,'rid'=>$roleId]);
            if ($type === 'RESIDENT') {
                $rv = $db->prepare("INSERT INTO resident_verification_requests (user_id,verification_status) VALUES (:uid,'PENDING')");
                $rv->execute(['uid'=>$userId]);
            }
            // Expiry is reset to 15 minutes from successful mail transport acceptance by the worker.
            $token = $db->prepare('INSERT INTO email_verification_tokens (user_id,token_hash,expires_at) VALUES (:uid,:hash,DATE_ADD(NOW(), INTERVAL 15 MINUTE))');
            $token->execute(['uid'=>$userId,'hash'=>$tokenHash]);
            $tokenId = (int)$db->lastInsertId();
            $baseUrl = self::configuredBaseUrl();
            $verifyUrl = $baseUrl . '/verify-email.php?token=' . rawurlencode($rawToken);
            $payload = json_encode(['verification_url'=>$verifyUrl,'first_name'=>$first], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($payload === false) throw new RuntimeException('Unable to prepare the verification email.');
            $mail = $db->prepare("INSERT INTO email_outbox (user_id,recipient_email,message_type,subject,payload_json,delivery_status,attempt_count,next_attempt_at) VALUES (:uid,:email,'EMAIL_VERIFICATION','Verify your Barangay San Antonio Portal account',:payload,'PENDING',0,NOW())");
            $mail->execute(['uid'=>$userId,'email'=>$email,'payload'=>$payload]);
            $db->commit();
            return $userId;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    private static function configuredBaseUrl(): string
    {
        $file = dirname(__DIR__, 2) . '/config/mail.php';
        if (!is_file($file)) throw new RuntimeException('Email settings are not configured. Copy config/mail.example.php to config/mail.php and set the portal base URL.');
        $cfg = require $file;
        $url = rtrim((string)($cfg['base_url'] ?? ''), '/');
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['http','https'], true)) throw new RuntimeException('Set a valid portal base_url in config/mail.php.');
        return $url;
    }

    private static function normalizePhilippineMobile(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);
        if ($digits === '') return null;
        if (str_starts_with($digits, '09') && strlen($digits) === 11) $digits = '63' . substr($digits, 1);
        elseif (str_starts_with($digits, '9') && strlen($digits) === 10) $digits = '63' . $digits;
        elseif (str_starts_with($digits, '639') && strlen($digits) === 12) { /* already normalized */ }
        else return null;
        return '+' . $digits;
    }

    private static function rateFile(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/rate-limits';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Registration rate-limit storage is unavailable.');
        return $dir . '/registration.json';
    }
    private static function enforceRateLimit(string $ip, string $email): void
    {
        $path = self::rateFile(); $fp = fopen($path, 'c+'); if (!$fp) throw new RuntimeException('Registration rate limit is unavailable.');
        try { flock($fp, LOCK_EX); $raw = stream_get_contents($fp); $rows = json_decode($raw ?: '[]', true); if (!is_array($rows)) $rows=[]; $now=time();
            $rows = array_values(array_filter($rows, static fn($r) => is_array($r) && (int)($r['at']??0) > $now-86400));
            $ipHash=hash('sha256',$ip); $emailHash=hash('sha256',$email);
            $ip15 = count(array_filter($rows, fn($r) => ($r['ip_hash']??'')===$ipHash && (int)$r['at']>$now-900));
            $ip24 = count(array_filter($rows, fn($r) => ($r['ip_hash']??'')===$ipHash));
            $em1 = count(array_filter($rows, fn($r) => ($r['email_hash']??'')===$emailHash && (int)$r['at']>$now-3600));
            if ($ip15 >= 5 || $ip24 >= 20 || $em1 >= 3) throw new RuntimeException('Too many registration attempts. Please wait before trying again.');
            $rows[]=['ip_hash'=>$ipHash,'email_hash'=>$emailHash,'at'=>$now];
            ftruncate($fp,0); rewind($fp); fwrite($fp,json_encode($rows)); fflush($fp);
        } finally { flock($fp, LOCK_UN); fclose($fp); }
    }
}
