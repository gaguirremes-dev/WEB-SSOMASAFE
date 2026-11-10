<?php
/**
 * Libro de Reclamaciones Virtual - SSOMA SAFE
 * Conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor)
 * y el D.S. N° 011-2011-PCM (modificado por D.S. 101-2021-PCM).
 */

// Session: started by reclamosIniciarSesion() (HttpOnly, SameSite=Lax, Secure on HTTPS).
date_default_timezone_set('America/Lima');

$smtpConfigFile = __DIR__ . '/../config.smtp.php';
if (file_exists($smtpConfigFile)) require $smtpConfigFile;

if (!defined('DOMINIO_PERMITIDO'))    define('DOMINIO_PERMITIDO',    'ssomasafe.com');
if (!defined('EMPRESA_RAZON_SOCIAL')) define('EMPRESA_RAZON_SOCIAL', 'SSOMA SAFE S.A.C.');
if (!defined('EMPRESA_RUC'))          define('EMPRESA_RUC',          '20607018287');
if (!defined('EMPRESA_DIRECCION'))    define('EMPRESA_DIRECCION',    'Av. República de Chile 324, Lima / Lapoint 1221, Chiclayo');
if (!defined('EMPRESA_EMAIL'))        define('EMPRESA_EMAIL',        'comercial@ssomasafe.com');
if (!defined('SMTP_PORT'))            define('SMTP_PORT',            465);
if (!defined('SMTP_FROM_NAME'))       define('SMTP_FROM_NAME',       'SSOMA SAFE');
if (!defined('EMPRESA_NOTIF_EMAIL'))  define('EMPRESA_NOTIF_EMAIL',  'reclamaciones@ssomasafe.com');
if (!defined('HCAPTCHA_SITE_KEY'))    define('HCAPTCHA_SITE_KEY',    '');
if (!defined('HCAPTCHA_SECRET_KEY'))  define('HCAPTCHA_SECRET_KEY',  '');

/* El RUC del config solo manda si es un RUC real de 11 digitos: un documento
   legal no puede mostrar un placeholder */
define('EMPRESA_RUC_PUBLICO', preg_match('/^[0-9]{11}$/', (string) EMPRESA_RUC) ? EMPRESA_RUC : '20607018287');


$recordsDir  = __DIR__ . '/reclamaciones';
$recordsFile = $recordsDir . '/records.json';
$lockFile    = $recordsDir . '/records.lock';

$logFile      = $recordsDir . '/security.log'; // directory is prepared by reclamosPrepararDirectorio()

$fpdfPath      = __DIR__ . '/../lib/fpdf.php';
$fpdfAvailable = file_exists($fpdfPath);
if ($fpdfAvailable) require_once $fpdfPath;

// ── Generar PDF ───────────────────────────────────────────────────────────────
function generarPDFReclamo(array $d): string {
    if (!class_exists('FPDF')) return '';
    $c = fn(string $s): string => iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    // Cabecera azul SSOMA
    $pdf->SetFillColor(0, 63, 140);
    $pdf->Rect(0, 0, 210, 38, 'F');
    $pdf->SetTextColor(127, 180, 229);
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetXY(10, 7);
    $pdf->Cell(0, 8, $c('HOJA DE RECLAMACIÓN VIRTUAL'), 0, 1, 'C');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetXY(10, 17);
    $pdf->Cell(0, 6, $c('Conforme a la Ley N 29571 - Codigo de Proteccion y Defensa del Consumidor'), 0, 1, 'C');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(10, 26);
    $pdf->Cell(0, 6, $c('Codigo: ' . $d['codigo'] . '     Fecha: ' . $d['fecha']), 0, 1, 'C');

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetY(44);

    // Proveedor
    $pdf->SetFillColor(239, 246, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('PROVEEDOR'), 0, 1, 'L', true);
    $pdf->SetFont('Arial', '', 9);
    $pdf->MultiCell(0, 5, $c('Razon Social: ' . EMPRESA_RAZON_SOCIAL . '   RUC: ' . EMPRESA_RUC_PUBLICO), 0, 'L');
    $pdf->MultiCell(0, 5, $c('Domicilio: ' . EMPRESA_DIRECCION), 0, 'L');
    $pdf->Ln(2);

    $col1W = 50; $col2W = 130;

    foreach ([
        ['1. IDENTIFICACION DEL CONSUMIDOR RECLAMANTE', [
            ['Nombre completo:', $d['nombres']],
            ['Documento:', $d['doc_tipo'] . ' N ' . $d['doc_nro']],
            ['Domicilio:', $d['direccion'] . ', ' . $d['distrito'] . ' - ' . $d['provincia'] . ' (' . $d['departamento'] . ')'],
            ['Telefono:', $d['telefono'] ?: '-'],
            ['Email:', $d['email']],
            ...($d['menor_edad'] ? [['Apoderado:', $d['apoderado_nombres'] . ' (' . $d['apoderado_doc_tipo'] . ' ' . $d['apoderado_doc_nro'] . ')']] : []),
        ]],
        ['2. IDENTIFICACION DEL BIEN CONTRATADO', [
            ['Sede:', $d['sede']],
            ['Servicio/Producto:', $d['servicio']],
            ['Tipo de bien:', ucfirst($d['bien_tipo'])],
            ['Monto reclamado:', 'S/. ' . $d['monto']],
        ]],
    ] as [$title, $rows]) {
        $pdf->SetFillColor(0, 63, 140);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(0, 6, $c($title), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        foreach ($rows as [$label, $val]) {
            $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell($col1W, 5.5, $c($label), 'B', 0, 'L');
            $pdf->SetFont('Arial', '', 8.5);  $pdf->Cell($col2W, 5.5, $c($val),   'B', 1, 'L');
        }
        $pdf->Ln(3);
    }

    // Sección 3
    $pdf->SetFillColor(0, 63, 140);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(0, 6, $c('3. DETALLE DE LA RECLAMACION Y PEDIDO DEL CONSUMIDOR'), 0, 1, 'L', true);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 9); $pdf->Cell(40, 6, $c('Tipo de incidencia:'), 0, 0);
    $pdf->SetFont('Arial', '', 9);
    $esReclamo = strtolower($d['reclamo_tipo']) === 'reclamo';
    $pdf->Cell(5, 6, $esReclamo ? 'X' : 'O', 1, 0, 'C');
    $pdf->Cell(22, 6, $c(' Reclamo'), 0, 0);
    $pdf->Cell(5, 6, !$esReclamo ? 'X' : 'O', 1, 0, 'C');
    $pdf->Cell(22, 6, $c(' Queja'), 0, 1);
    $pdf->Ln(1);
    $pdf->SetFillColor(248, 250, 255);
    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell(0, 5, $c('Detalle del hecho:'), 0, 1);
    $pdf->SetFont('Arial', '', 8.5);  $pdf->MultiCell(0, 5, $c($d['detalle']), 1, 'L', true);
    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 8.5); $pdf->Cell(0, 5, $c('Pedido del consumidor:'), 0, 1);
    $pdf->SetFont('Arial', '', 8.5);  $pdf->MultiCell(0, 5, $c($d['pedido']), 1, 'L', true);
    $pdf->Ln(4);

    $pdf->SetFont('Arial', '', 8.5);
    $pdf->Cell(90, 5, $c('Firma del Consumidor'), 'T', 0, 'C');
    $pdf->Cell(10, 5, '', 0, 0);
    $pdf->Cell(90, 5, $c('Firma del Proveedor'), 'T', 1, 'C');
    $pdf->Ln(2);

    $pdf->SetFont('Arial', 'I', 7.5);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(0, 4, $c('La formulacion del reclamo no impide acudir a otras vias de solucion de controversias ni es requisito previo para interponer una denuncia ante el INDECOPI.'), 0, 'L');
    $pdf->SetFont('Arial', 'BI', 7.5);
    $pdf->MultiCell(0, 4, $c('El proveedor debe dar respuesta al reclamo o queja en un plazo no mayor a treinta (30) dias calendario (Ley N 29571).'), 0, 'L');

    return $pdf->Output('', 'S');
}

// ══════════════════════════════════════════════════════════════════════════════
// HARDENING CORE - identical in every complaints book. Keep the six copies in sync.
// ══════════════════════════════════════════════════════════════════════════════

// Optional limits / switches (override them in config.smtp.php).
foreach ([
    'RATE_LIMIT_MAX'      => 1,     // submissions per IP inside RATE_LIMIT_WINDOW
    'RATE_LIMIT_WINDOW'   => 1800,  // seconds (30 min)
    'RATE_LIMIT_DAY_MAX'  => 5,     // submissions per IP per 24 h
    'SITE_LIMIT_HOUR_MAX' => 20,    // site-wide submissions per hour
    'SITE_LIMIT_DAY_MAX'  => 80,    // site-wide submissions per 24 h
    'SMTP_VERIFY_TLS'     => true,  // verify the SMTP server certificate
    'IP_HASH_SALT'        => '',    // optional; empty = random per-install key file
] as $reclamosK => $reclamosV) {
    if (!defined($reclamosK)) define($reclamosK, $reclamosV);
}

// Cloudflare published ranges (https://www.cloudflare.com/ips/). Review from time to time.
define('RECLAMOS_CF_RANGES', [
    '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
    '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
    '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
    '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
    '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
    '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
]);
const RECLAMOS_MAX_BODY  = 65536; // reject bigger POST bodies (bytes)
const RECLAMOS_MIN_AGE   = 5;     // seconds after render before a submit is accepted
const RECLAMOS_MAX_AGE   = 7200;  // seconds after render before the form expires

function reclamosCabecerasSeguridad(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header_remove('X-Powered-By');
}

function reclamosIniciarSesion(): void {
    if (session_status() !== PHP_SESSION_NONE) return;
    $https = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
          || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443
          || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https' && reclamosEsCloudflare((string)($_SERVER['REMOTE_ADDR'] ?? '')));
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.use_only_cookies', '1');
    session_cache_limiter(''); // keep our own Cache-Control header
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ── Client IP: REMOTE_ADDR, or CF-Connecting-IP only when the peer is Cloudflare ──
function reclamosNormalizarIp(string $ip): string {
    $ip = trim($ip);
    if (preg_match('/^::ffff:(\d{1,3}(?:\.\d{1,3}){3})$/i', $ip, $m)) return $m[1];
    return $ip;
}
function reclamosIpEnCidr(string $ip, string $cidr): bool {
    $parts  = explode('/', $cidr, 2);
    $ipBin  = @inet_pton($ip);
    $netBin = @inet_pton($parts[0]);
    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) return false;
    $bits  = isset($parts[1]) ? (int)$parts[1] : strlen($ipBin) * 8;
    $bytes = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) return false;
    if ($rem === 0) return true;
    $mask = (0xFF << (8 - $rem)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
}
function reclamosEsCloudflare(string $ip): bool {
    $ip = reclamosNormalizarIp($ip);
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) return false;
    foreach (RECLAMOS_CF_RANGES as $cidr) {
        if (reclamosIpEnCidr($ip, $cidr)) return true;
    }
    return false;
}
function reclamosClientIp(): string {
    $remote = reclamosNormalizarIp((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($remote, FILTER_VALIDATE_IP) === false) return '0.0.0.0';
    if (reclamosEsCloudflare($remote)) {
        $cf = reclamosNormalizarIp((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP) !== false) return $cf;
    }
    return $remote;
}

// ── Records directory: protected, never listable, per-install secret key ─────
function reclamosPrepararDirectorio(string $dir): bool {
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return false;
    $ht  = $dir . '/.htaccess';
    $cur = is_file($ht) ? (string)@file_get_contents($ht) : '';
    if (strpos($cur, 'Require all denied') === false || strpos($cur, 'Options -Indexes') === false) {
        @file_put_contents($ht,
            "# Protected records directory: never served over HTTP.\n" .
            "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n" .
            "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n" .
            "Options -Indexes\n");
    }
    if (!is_file($dir . '/index.html')) @file_put_contents($dir . '/index.html', '');
    return is_writable($dir);
}
function reclamosClaveInstalacion(string $dir): string {
    static $cache = [];
    if (isset($cache[$dir])) return $cache[$dir];
    $path = $dir . '/install.key';
    $key  = is_file($path) ? trim((string)@file_get_contents($path)) : '';
    if (strlen($key) < 32) {
        $fp = @fopen($path, 'c+');
        $key = '';
        if ($fp && flock($fp, LOCK_EX)) {
            $existing = trim((string)stream_get_contents($fp));
            if (strlen($existing) >= 32) {
                $key = $existing;
            } else {
                $new = bin2hex(random_bytes(32));
                ftruncate($fp, 0); rewind($fp); fwrite($fp, $new); fflush($fp);
                @chmod($path, 0600);
                $key = $new;
            }
            flock($fp, LOCK_UN);
        }
        if ($fp) fclose($fp);
    }
    return $cache[$dir] = $key;
}
// Salted SHA-256 of the IP: the only form in which an IP is ever stored.
function reclamosHashIp(string $ip, string $dir): string {
    $salt = (string)IP_HASH_SALT;
    if ($salt === '') $salt = reclamosClaveInstalacion($dir);
    if ($salt === '') return '';
    return hash('sha256', $salt . '|' . $ip);
}
function reclamosHuellaContenido(string $dir, string $detalle, string $email): string {
    $norm = mb_strtolower(preg_replace('/\s+/u', ' ', trim($detalle)), 'UTF-8') . '|' . strtolower($email);
    return hash_hmac('sha256', $norm, reclamosClaveInstalacion($dir));
}

// ── Logging (never secrets, bodies or personal data) ─────────────────────────
function logSecurityEvent(string $logFile, string $event, array $context = []): void {
    foreach (['email','nombres','doc_nro','apoderado_nombres','apoderado_doc_nro','detalle','pedido'] as $k) unset($context[$k]);
    if (@is_file($logFile) && @filesize($logFile) > 2097152) @rename($logFile, $logFile . '.1'); // crude rotation
    $line = date('Y-m-d H:i:s') . ' | ' . $event . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ── Time trap: HMAC-signed render timestamp, bound to the session CSRF token ──
function reclamosTokenTiempo(string $dir, string $csrf): string {
    $key = reclamosClaveInstalacion($dir);
    if ($key === '' || $csrf === '') return '';
    $ts = (string)time();
    return $ts . '.' . hash_hmac('sha256', 'ts|' . $ts . '|' . $csrf, $key);
}
// Returns '' when valid, otherwise a short reason code.
function reclamosVerificarTiempo(string $dir, string $csrf, string $token): string {
    $key = reclamosClaveInstalacion($dir);
    if ($key === '' || $csrf === '') return 'TT_NO_KEY';
    if (!preg_match('/^(\d{9,11})\.([a-f0-9]{64})$/', $token, $m)) return 'TT_MALFORMED';
    $expected = hash_hmac('sha256', 'ts|' . $m[1] . '|' . $csrf, $key);
    if (!hash_equals($expected, $m[2])) return 'TT_BAD_SIGNATURE';
    $age = time() - (int)$m[1];
    if ($age < RECLAMOS_MIN_AGE) return 'TT_TOO_FAST';
    if ($age > RECLAMOS_MAX_AGE) return 'TT_EXPIRED';
    return '';
}

// ── Origin / Referer: compare parsed hosts, never substrings ─────────────────
function reclamosHostPermitido(string $host): bool {
    $host = rtrim(strtolower(trim($host)), '.');
    $dom  = strtolower((string)DOMINIO_PERMITIDO);
    return $host !== '' && ($host === $dom || $host === 'www.' . $dom);
}
function reclamosOrigenValido(): bool {
    $origin  = trim((string)($_SERVER['HTTP_ORIGIN']  ?? ''));
    $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
    if ($origin !== '') return reclamosHostPermitido((string)parse_url($origin, PHP_URL_HOST)); // "null" -> rejected
    if ($referer !== '') return reclamosHostPermitido((string)parse_url($referer, PHP_URL_HOST));
    return true; // some privacy setups strip both; CSRF + captcha + time trap still apply
}

// ── hCaptcha: fails closed (no keys / no answer / bad hostname => false) ─────
function verificarHCaptcha(string $token, string $secretKey): bool {
    if ($token === '' || strlen($token) > 4096 || $secretKey === '') return false;
    $post = ['secret' => $secretKey, 'response' => $token];
    if (HCAPTCHA_SITE_KEY !== '') $post['sitekey'] = HCAPTCHA_SITE_KEY;
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query($post),
        'timeout' => 10,
    ]]);
    $result = @file_get_contents('https://hcaptcha.com/siteverify', false, $ctx);
    if ($result === false) return false;
    $data = json_decode($result, true);
    if (!is_array($data) || ($data['success'] ?? false) !== true) return false;
    if (isset($data['hostname']) && is_string($data['hostname']) && $data['hostname'] !== '') {
        if (!reclamosHostPermitido($data['hostname'])) return false;
    }
    return true;
}

// ── Input helpers ─────────────────────────────────────────────────────────────
function sanitizarCabecera(string $s): string {
    $s = str_ireplace(['%0d', '%0a'], '', $s);
    return trim((string)preg_replace('/[\x00-\x1F\x7F]+/', '', $s));
}
function esc(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
// Strips tags, control chars and invisible bidi/format chars; enforces UTF-8 and max length.
function limpiarTexto(?string $s, int $maxLen = 500, bool $multiline = false): string {
    if ($s === null || $s === '') return '';
    if (!mb_check_encoding($s, 'UTF-8')) return '';
    $s = strip_tags($s);
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = $multiline
        ? (string)preg_replace('/[\x00-\x09\x0B-\x1F\x7F]/', '', $s)
        : (string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', $s);
    $s = (string)preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u', '', $s);
    if ($multiline) $s = (string)preg_replace("/\n{3,}/", "\n\n", $s);
    return mb_substr(trim($s), 0, $maxLen, 'UTF-8');
}
// Valid address (<= 254 chars, no header-breaking chars) or '' when invalid.
function reclamosEmail($raw): string {
    $e = trim((string)$raw);
    if ($e === '' || strlen($e) > 254 || preg_match('/[\x00-\x20\x7F<>,;"\'\\\\]/', $e)) return '';
    $ok = filter_var($e, FILTER_VALIDATE_EMAIL);
    return $ok === false ? '' : (string)$ok;
}
// Spam heuristics: more than 2 URLs in the free text, <a ...> tags or [url=] BBCode.
function reclamosContenidoSospechoso(array $textos): bool {
    $all = implode("\n", array_map('strval', $textos));
    if (preg_match('/<\s*a[\s>]/i', $all) || preg_match('/\[\s*(?:url|link)\b/i', $all)) return true;
    return preg_match_all('~(?:https?://|ftp://|www\.)\S+~i', $all) > 2;
}

// ── Request guards. Returns '' when everything passes, else a Spanish message ─
function reclamosGuardarSolicitud(string $dir, string $logFile, string $ipHash): string {
    $log = function (string $ev, array $ctx = []) use ($logFile, $ipHash): void {
        logSecurityEvent($logFile, $ev, $ctx + ['ip_hash' => $ipHash]);
    };
    $contacto = defined('EMPRESA_EMAIL') ? ' o escríbenos a ' . EMPRESA_EMAIL : '';

    if ($ipHash === '') {
        $log('NO_KEY_AVAILABLE');
        return 'No pudimos procesar tu solicitud en este momento. Inténtalo más tarde' . $contacto . '.';
    }
    if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > RECLAMOS_MAX_BODY) {
        $log('PAYLOAD_TOO_LARGE');
        http_response_code(413);
        return 'La solicitud es demasiado grande. Reduce el texto e inténtalo de nuevo.';
    }
    // Honeypot: off-screen field, humans never fill it. Answer 200 and do nothing.
    if (!empty($_POST['hp_website'] ?? '')) {
        $log('HONEYPOT_TRIGGERED');
        http_response_code(200);
        exit;
    }
    foreach ($_POST as $v) {
        if (!is_string($v) || !mb_check_encoding($v, 'UTF-8')) {
            $log('BAD_INPUT_ENCODING');
            return 'El formulario contiene datos no válidos. Recarga la página e inténtalo de nuevo.';
        }
    }
    $csrfPost = (string)($_POST['csrf_token'] ?? '');
    $csrfSess = (string)($_SESSION['csrf_token'] ?? '');
    if ($csrfPost === '' || $csrfSess === '' || !hash_equals($csrfSess, $csrfPost)) {
        $log('CSRF_FAILED');
        return 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    }
    $tt = reclamosVerificarTiempo($dir, $csrfSess, (string)($_POST['form_ts'] ?? ''));
    if ($tt !== '') {
        $log('TIME_TRAP', ['reason' => $tt]);
        return $tt === 'TT_TOO_FAST'
            ? 'Enviaste el formulario demasiado rápido. Espera unos segundos e inténtalo de nuevo.'
            : 'El formulario expiró o no es válido. Revisa tus datos e inténtalo de nuevo.';
    }
    if (!reclamosOrigenValido()) {
        $log('ORIGIN_REJECTED');
        return 'Solicitud rechazada. Recarga la página e inténtalo de nuevo.';
    }
    if (reclamosContenidoSospechoso([$_POST['detalle'] ?? '', $_POST['pedido'] ?? '', $_POST['bien_desc'] ?? '', $_POST['servicio'] ?? ''])) {
        $log('SPAM_CONTENT');
        return 'Tu mensaje contiene enlaces o formato no permitido. Quita los enlaces e inténtalo de nuevo.';
    }
    if (HCAPTCHA_SECRET_KEY === '' || HCAPTCHA_SITE_KEY === '') {
        $log('CAPTCHA_NOT_CONFIGURED');
        return 'El servicio no está disponible temporalmente. Inténtalo más tarde' . $contacto . '.';
    }
    if (!verificarHCaptcha((string)($_POST['h-captcha-response'] ?? ''), HCAPTCHA_SECRET_KEY)) {
        $log('CAPTCHA_FAILED');
        return 'Por favor, completa el desafío de seguridad (captcha) antes de enviar.';
    }
    return '';
}

// ── Atomic rate limit (per IP-hash + site-wide) and duplicate-content check ──
// Reserves the slot when everything passes. Returns '' on success, else a Spanish message.
function reclamosReservarEnvio(string $dir, string $ipHash, string $dupHash, string $logFile): string {
    $contacto = defined('EMPRESA_EMAIL') ? ' o escríbenos a ' . EMPRESA_EMAIL : '';
    $generic  = 'No pudimos registrar tu solicitud en este momento. Inténtalo más tarde' . $contacto . '.';
    $stateFile = $dir . '/rate_state.json';
    @unlink($dir . '/rate_limits.json');      // legacy file kept raw IPs
    @unlink($dir . '/rate_limits.json.lock');
    $fp = @fopen($stateFile . '.lock', 'c');
    if (!$fp || !flock($fp, LOCK_EX)) {
        if ($fp) fclose($fp);
        logSecurityEvent($logFile, 'RATE_LOCK_FAILED', ['ip_hash' => $ipHash]);
        return $generic; // fail closed
    }
    $now  = time();
    $data = [];
    if (is_file($stateFile)) {
        $raw  = (string)@file_get_contents($stateFile);
        $data = $raw !== '' ? (json_decode($raw, true) ?: []) : [];
    }
    $ips  = is_array($data['ip']   ?? null) ? $data['ip']   : [];
    $site = is_array($data['site'] ?? null) ? $data['site'] : [];
    $dups = is_array($data['dup']  ?? null) ? $data['dup']  : [];
    $fresh = function ($ts) use ($now): bool { return is_int($ts) && ($now - $ts) < 86400; };
    $site = array_values(array_filter($site, $fresh));
    $dups = array_filter($dups, $fresh);
    foreach ($ips as $h => $list) {
        $ips[$h] = is_array($list) ? array_values(array_filter($list, $fresh)) : [];
        if (!$ips[$h]) unset($ips[$h]);
    }
    $mine     = $ips[$ipHash] ?? [];
    $inWindow = count(array_filter($mine, function ($t) use ($now) { return ($now - $t) < RATE_LIMIT_WINDOW; }));
    $siteHour = count(array_filter($site, function ($t) use ($now) { return ($now - $t) < 3600; }));
    $err = '';
    if ($inWindow >= RATE_LIMIT_MAX) {
        $err = 'RATE_LIMIT_IP';
        $msg = 'Has enviado un reclamo recientemente. Por favor, espera ' . (int)ceil(RATE_LIMIT_WINDOW / 60) . ' minutos antes de volver a intentarlo.';
    } elseif (count($mine) >= RATE_LIMIT_DAY_MAX) {
        $err = 'RATE_LIMIT_IP_DAY';
        $msg = 'Has alcanzado el límite diario de envíos desde tu conexión. Inténtalo nuevamente mañana' . $contacto . '.';
    } elseif ($siteHour >= SITE_LIMIT_HOUR_MAX || count($site) >= SITE_LIMIT_DAY_MAX) {
        $err = 'RATE_LIMIT_SITE';
        $msg = 'Estamos recibiendo muchas solicitudes en este momento. Inténtalo nuevamente en unos minutos' . $contacto . '.';
    } elseif ($dupHash !== '' && isset($dups[$dupHash])) {
        $err = 'DUPLICATE_CONTENT';
        $msg = 'Ya recibimos un reclamo con el mismo contenido. Si necesitas ayuda, comunícate con nosotros' . $contacto . '.';
    }
    if ($err !== '') {
        flock($fp, LOCK_UN); fclose($fp);
        logSecurityEvent($logFile, $err, ['ip_hash' => $ipHash]);
        return $msg;
    }
    $mine[]         = $now;
    $ips[$ipHash]   = $mine;
    $site[]         = $now;
    if ($dupHash !== '') $dups[$dupHash] = $now;
    $written = @file_put_contents($stateFile, json_encode(['ip' => $ips, 'site' => $site, 'dup' => $dups]));
    flock($fp, LOCK_UN); fclose($fp);
    if ($written === false) {
        logSecurityEvent($logFile, 'RATE_WRITE_FAILED', ['ip_hash' => $ipHash]);
        return $generic;
    }
    return '';
}

// ── SMTP (implicit TLS on 465, STARTTLS otherwise; certificate verified by default) ──
function enviarCorreoSMTP(string $toEmail, string $subject, string $htmlBody, string $pdfB64 = '', string $pdfName = '', string &$err = ''): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        $err = 'SMTP configuration missing.';
        return false;
    }
    $toEmail = sanitizarCabecera($toEmail);
    if (strlen($toEmail) > 254 || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $err = 'Invalid recipient address.';
        return false;
    }
    $subject   = mb_substr(sanitizarCabecera($subject), 0, 200, 'UTF-8');
    $pdfName   = (string)preg_replace('/[^A-Za-z0-9._-]/', '_', $pdfName);
    $fromEmail = sanitizarCabecera(SMTP_USER);
    $replyTo   = sanitizarCabecera(EMPRESA_EMAIL);
    $enc = function (string $s): string { return '=?UTF-8?B?' . base64_encode($s) . '?='; };
    $eol = "\r\n";
    $headers  = 'Date: ' . date('r') . $eol;
    $headers .= 'From: ' . $enc(sanitizarCabecera(SMTP_FROM_NAME)) . ' <' . $fromEmail . '>' . $eol;
    $headers .= 'To: <' . $toEmail . '>' . $eol;
    $headers .= 'Subject: ' . $enc($subject) . $eol;
    $headers .= 'Reply-To: ' . $replyTo . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    if ($pdfB64) {
        $boundary = '----=_Part_' . md5(uniqid('', true));
        $headers .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $eol;
        $body  = '--' . $boundary . $eol;
        $body .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body .= 'Content-Transfer-Encoding: 7bit' . $eol . $eol;
        $body .= $htmlBody . $eol;
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Type: application/pdf; name="' . $pdfName . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment; filename="' . $pdfName . '"' . $eol . $eol;
        $body .= $pdfB64 . $eol;
        $body .= '--' . $boundary . '--';
    } else {
        $headers .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body = $htmlBody;
    }
    $message = $headers . $eol . $body;
    $message = str_replace(["\r\n", "\r", "\n"], "\n", $message);
    $message = str_replace("\n", $eol, $message);
    $message = str_replace($eol . '.', $eol . '..', $message);

    $verify = filter_var(SMTP_VERIFY_TLS, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $verify = ($verify === null) ? true : $verify;
    $sslOpts = ['verify_peer' => $verify, 'verify_peer_name' => $verify, 'allow_self_signed' => !$verify, 'SNI_enabled' => true];
    if ($verify) $sslOpts['peer_name'] = SMTP_HOST;
    $ctx = stream_context_create(['ssl' => $sslOpts]);
    $implicitTls = in_array((int)SMTP_PORT, [465, 4655], true);
    $fp = @stream_socket_client(($implicitTls ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $err = "SMTP connection failed: $errstr ($errno)"; return false; }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) { $data .= $line; if (strlen($line) < 4 || $line[3] === ' ') break; }
        return $data;
    };
    $cmd = function (string $c) use ($fp, $read): string { fwrite($fp, $c . "\r\n"); return $read(); };
    $ok  = function (string $resp, array $codes) use (&$err): bool {
        foreach ($codes as $code) { if (strncmp($resp, $code, strlen($code)) === 0) return true; }
        $err = trim($resp); return false;
    };
    $fail = function () use ($fp) { @fwrite($fp, "QUIT\r\n"); @fclose($fp); return false; };

    if (!$ok($read(), ['220']))                                   return $fail();
    if (!$ok($cmd('EHLO ' . SMTP_HOST), ['250']))                 return $fail();
    if (!$implicitTls) { // never send credentials in clear text
        if (!$ok($cmd('STARTTLS'), ['220']))                      return $fail();
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err = 'STARTTLS negotiation failed.'; return $fail(); }
        if (!$ok($cmd('EHLO ' . SMTP_HOST), ['250']))             return $fail();
    }
    if (!$ok($cmd('AUTH LOGIN'), ['334']))                        return $fail();
    if (!$ok($cmd(base64_encode(SMTP_USER)), ['334']))            return $fail();
    if (!$ok($cmd(base64_encode(SMTP_PASS)), ['235']))            return $fail();
    if (!$ok($cmd('MAIL FROM:<' . $fromEmail . '>'), ['250']))    return $fail();
    if (!$ok($cmd('RCPT TO:<' . $toEmail . '>'), ['250', '251'])) return $fail();
    if (!$ok($cmd('DATA'), ['354']))                              return $fail();
    if (!$ok($cmd($message . "\r\n."), ['250']))                  return $fail();
    $cmd('QUIT'); fclose($fp);
    return true;
}

// ── Runtime bootstrap (hardened) ──────────────────────────────────────────────
@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
reclamosCabecerasSeguridad();
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'POST'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    exit;
}
reclamosIniciarSesion();

$success = false; $errorMsg = ''; $generatedCode = ''; $submittedData = []; $debugLog = [];
if (!reclamosPrepararDirectorio($recordsDir)) {
    $errorMsg = 'El servicio no está disponible temporalmente. Inténtalo más tarde o escríbenos a ' . EMPRESA_EMAIL . '.';
}
// The raw IP is never kept: $clientIP holds the salted hash (it is what the logs record).
$ipHash   = reclamosHashIp(reclamosClientIp(), $recordsDir);
$clientIP = $ipHash;

set_error_handler(function ($errno) use (&$errorMsg) {
    if ($errno === E_ERROR || $errno === E_USER_ERROR) $errorMsg = 'Error interno del servidor. Por favor, inténtelo más tarde.';
    return true;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMsg === '') {

    // ── 1-5. Guards: size, honeypot, encoding, CSRF, time trap, origin, spam, captcha ──
    $errorMsg = reclamosGuardarSolicitud($recordsDir, $logFile, $ipHash);

    // 6. Sanitizar y validar
    if (empty($errorMsg)) {
        $nombres      = limpiarTexto($_POST['nombres']      ?? '', 200);
        $doc_tipo     = $_POST['doc_tipo']     ?? '';
        $doc_nro      = limpiarTexto($_POST['doc_nro']      ?? '', 20);
        $email        = reclamosEmail($_POST['email'] ?? '');
        $telefono     = limpiarTexto($_POST['telefono']     ?? '', 20);
        $direccion    = limpiarTexto($_POST['direccion']    ?? '', 300);
        $departamento = limpiarTexto($_POST['departamento'] ?? '', 100);
        $provincia    = limpiarTexto($_POST['provincia']    ?? '', 100);
        $distrito     = limpiarTexto($_POST['distrito']     ?? '', 100);
        $menor_edad   = isset($_POST['menor_edad']);
        $ap_nombres   = limpiarTexto($_POST['apoderado_nombres']  ?? '', 200);
        $ap_doc_tipo  = $_POST['apoderado_doc_tipo'] ?? '';
        $ap_doc_nro   = limpiarTexto($_POST['apoderado_doc_nro']  ?? '', 20);
        $sede         = limpiarTexto($_POST['sede']         ?? '', 100);
        $servicio     = limpiarTexto($_POST['servicio']     ?? '', 300);
        $bien_tipo    = $_POST['bien_tipo']    ?? '';
        $reclamo_tipo = $_POST['reclamo_tipo'] ?? '';
        $detalle      = limpiarTexto($_POST['detalle']      ?? '', 2000, true);
        $pedido       = limpiarTexto($_POST['pedido']       ?? '', 2000, true);
        $monto        = number_format(max(0.0, (float)preg_replace('/[^\d.]/', '', $_POST['monto'] ?? '0')), 2, '.', '');

        if (!in_array($doc_tipo,     ['DNI','CE','PASAPORTE','RUC'], true)) $doc_tipo     = 'DNI';
        if (!in_array($ap_doc_tipo,  ['DNI','CE','PASAPORTE'],        true)) $ap_doc_tipo  = 'DNI';
        if (!in_array($bien_tipo,    ['producto','servicio'],          true)) $bien_tipo    = 'servicio';
        if (!in_array($reclamo_tipo, ['reclamo','queja'],              true)) $reclamo_tipo = 'reclamo';

        $sedesValidas = ['Lima — Av. República de Chile 324', 'Chiclayo — Lapoint 1221', 'Servicio online / plataforma digital'];
        if (!in_array($sede, $sedesValidas, true)) $sede = 'No especificada';

        if (!$nombres || !$doc_nro || !$email || !$direccion || !$detalle || !$pedido) {
            $errorMsg = 'Por favor, rellene todos los campos obligatorios.';
        }
    }

    // ── 6b. Atomic rate limit (IP + site-wide) and duplicate-content check ─────
    if (empty($errorMsg)) {
        $errorMsg = reclamosReservarEnvio($recordsDir, $ipHash, reclamosHuellaContenido($recordsDir, $detalle, $email), $logFile);
    }

    // 7. Guardar registro
    if (empty($errorMsg)) {
        $fp = fopen($lockFile, 'w');
        if ($fp && flock($fp, LOCK_EX)) {
            $year    = date('Y');
            $records = file_exists($recordsFile) ? (json_decode(file_get_contents($recordsFile), true) ?: []) : [];
            $count   = count(array_filter($records, fn($r) => ($r['year'] ?? '') == $year));
            $generatedCode = sprintf('REC-%s-%04d', $year, $count + 1);
            $submittedData = [
                'codigo' => $generatedCode, 'year' => $year, 'fecha' => date('d/m/Y h:i A'),
                'nombres' => $nombres, 'doc_tipo' => $doc_tipo, 'doc_nro' => $doc_nro,
                'email' => $email, 'telefono' => $telefono, 'direccion' => $direccion,
                'departamento' => $departamento, 'provincia' => $provincia, 'distrito' => $distrito,
                'menor_edad' => $menor_edad,
                'apoderado_nombres'  => $menor_edad ? $ap_nombres  : '',
                'apoderado_doc_tipo' => $menor_edad ? $ap_doc_tipo : '',
                'apoderado_doc_nro'  => $menor_edad ? $ap_doc_nro  : '',
                'sede' => $sede, 'servicio' => $servicio,
                'bien_tipo' => $bien_tipo, 'monto' => $monto,
                'reclamo_tipo' => $reclamo_tipo, 'detalle' => $detalle, 'pedido' => $pedido,
                'estado' => 'Pendiente',
            ];
            $records[] = $submittedData;
            file_put_contents($recordsFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            flock($fp, LOCK_UN);
            $success = true;
        } else {
            $errorMsg = 'Error al registrar en el servidor. Inténtelo nuevamente.';
        }
        if ($fp) fclose($fp);
    }

    // 8. Generar PDF + enviar correos + redirigir
    if ($success) {
        logSecurityEvent($logFile, 'RECLAMO_OK', ['ip' => $clientIP, 'codigo' => $generatedCode]);
        $pdfBytes = '';
        if ($fpdfAvailable) { try { $pdfBytes = generarPDFReclamo($submittedData); } catch(Exception $e) { } }
        $pdfB64  = $pdfBytes ? chunk_split(base64_encode($pdfBytes)) : '';
        $pdfName = 'Hoja_Reclamacion_' . $generatedCode . '.pdf';
        if ($pdfBytes) @file_put_contents($recordsDir . '/' . $pdfName, $pdfBytes);

        $emailConsumidor = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;'>
            <div style='background:#003F8C;padding:25px;text-align:center;'>
                <h2 style='margin:0;font-size:22px;color:#7FB4E5;'>HOJA DE RECLAMACIÓN VIRTUAL</h2>
                <p style='margin:5px 0 0;color:#fff;font-size:14px;font-weight:bold;'>Código: " . esc($generatedCode) . "</p>
            </div>
            <div style='padding:25px;line-height:1.6;'>
                <p>Estimado(a) <strong>" . esc($nombres) . "</strong>,</p>
                <p>Confirmamos la recepción de tu reclamación registrada el <strong>" . date('d/m/Y') . "</strong>. Adjunto encontrarás el cargo de tu Hoja de Reclamación Virtual.</p>
                <p>Daremos respuesta en un plazo máximo de <strong>30 días calendario</strong> conforme a la Ley N° 29571.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
                <table style='width:100%;border-collapse:collapse;font-size:14px;'>
                    <tr><td style='padding:6px 0;font-weight:bold;width:160px;'>Consumidor:</td><td>" . esc($nombres) . " (" . esc($doc_tipo) . " " . esc($doc_nro) . ")</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Sede:</td><td>" . esc($sede) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Servicio/Producto:</td><td>" . esc($servicio) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Monto reclamado:</td><td>S/. " . esc($monto) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;'>Incidencia:</td><td style='font-weight:bold;color:" . ($reclamo_tipo==='reclamo'?'#dc2626':'#d97706') . ";text-transform:capitalize;'>" . esc($reclamo_tipo) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;vertical-align:top;'>Detalle:</td><td style='background:#f9f9f9;padding:8px;border-radius:4px;'>" . nl2br(esc($detalle)) . "</td></tr>
                    <tr><td style='padding:6px 0;font-weight:bold;vertical-align:top;'>Pedido:</td><td style='background:#f9f9f9;padding:8px;border-radius:4px;'>" . nl2br(esc($pedido)) . "</td></tr>
                </table>
            </div>
            <div style='background:#f5f5f5;padding:15px;text-align:center;font-size:12px;color:#888;border-top:1px solid #e0e0e0;'>Cargo automático — no responder a este mensaje · SSOMA SAFE S.A.C.</div>
        </div>";

        $errC = '';
        $mailClienteSubj = 'Cargo de Hoja de Reclamación N° ' . $generatedCode . ' — SSOMA SAFE';
        $mailClienteBody = $emailConsumidor;

        $emailEmpresa = "
        <div style='font-family:Arial,sans-serif;color:#333;max-width:600px;margin:0 auto;border:1px solid #e0e0e0;border-radius:8px;padding:25px;'>
            <h2 style='color:#dc2626;margin-top:0;'>Nueva Reclamación — " . esc($generatedCode) . "</h2>
            <p>Plazo máximo de respuesta: <strong>30 días calendario</strong>.</p>
            <p><strong>Reclamante:</strong> " . esc($nombres) . " · " . esc($doc_tipo) . " " . esc($doc_nro) . " · " . esc($email) . " · " . esc($telefono) . "</p>
            <p><strong>Domicilio:</strong> " . esc($direccion) . ", " . esc($distrito) . " - " . esc($provincia) . " (" . esc($departamento) . ")</p>
            <p><strong>Sede:</strong> " . esc($sede) . "</p>
            <p><strong>Servicio/Producto:</strong> " . esc($servicio) . " · S/. " . esc($monto) . "</p>
            <p><strong>Tipo:</strong> " . esc(strtoupper($reclamo_tipo)) . "</p>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #dc2626;margin-bottom:10px;'><strong>Detalle:</strong><br>" . nl2br(esc($detalle)) . "</div>
            <div style='background:#f7f7f7;padding:12px;border-left:4px solid #003F8C;'><strong>Pedido:</strong><br>" . nl2br(esc($pedido)) . "</div>
        </div>";
        $errE = '';
        $okEmpresa = enviarCorreoSMTP(EMPRESA_NOTIF_EMAIL, 'NUEVA RECLAMACIÓN N° ' . $generatedCode . ' — ' . $nombres, $emailEmpresa, $pdfB64, $pdfName, $errE);
        if ($okEmpresa) {
            // Consumer copy only after the internal mail was accepted (anti mail-bombing).
            if (!enviarCorreoSMTP($email, $mailClienteSubj, $mailClienteBody, $pdfB64, $pdfName, $errC)) {
                logSecurityEvent($logFile, 'SMTP_CLIENT_FAILED', ['detail' => substr($errC, 0, 160)]);
            }
        } else {
            logSecurityEvent($logFile, 'SMTP_INTERNAL_FAILED', ['detail' => substr($errE, 0, 160)]);
        }

        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
        session_regenerate_id(true);
        $_SESSION['reclamo_success'] = $submittedData;
        header('Location: libro-de-reclamaciones.php?ok=1');
        exit;
    }
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_GET['ok']) && !empty($_SESSION['reclamo_success'])) {
    $success       = true;
    $submittedData = $_SESSION['reclamo_success'];
    $generatedCode = $submittedData['codigo'] ?? '';
    unset($_SESSION['reclamo_success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <title>Libro de Reclamaciones | SSOMA SAFE — Perú</title>
    <meta name="description" content="Libro de Reclamaciones Virtual de SSOMA SAFE. Registra tu queja o reclamo conforme al Código de Protección al Consumidor — Ley N.° 29571 — INDECOPI.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://www.ssomasafe.com/Libro-reclamaciones/">
    <link rel="icon" type="image/jpeg" href="../assets/img/favicon-ssoma.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { primary: '#003F8C', light: '#7FB4E5', dark: '#1E293B', pale: '#EFF6FF' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Manrope', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { background: #F5F7FA; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .form-input {
            width: 100%; background: #FAFBFC; border: 1.5px solid #E2E8F0; border-radius: 0.75rem;
            padding: 0.8rem 1rem; color: #1E293B; font-size: 0.9rem; font-family: 'Inter', sans-serif;
            transition: border-color .2s, box-shadow .2s, background .2s; outline: none;
        }
        .form-input::placeholder { color: #9CA3AF; }
        .form-input:focus { border-color: #003F8C; background: #fff; box-shadow: 0 0 0 3px rgba(0,63,140,.12); }
        select.form-input { appearance: none; background-color: #FAFBFC; color: #1E293B;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right .75rem center; background-size: 1rem; padding-right: 2.5rem; }
        .form-label { display: block; font-size: .8125rem; font-weight: 600; color: #374151; margin-bottom: .4rem; }
        .card { background: #fff; border: 1px solid rgba(0,63,140,.07); border-radius: 1.25rem; box-shadow: 0 8px 40px rgba(0,63,140,.07), 0 2px 8px rgba(0,0,0,.04); }
        .btn-primary { background: linear-gradient(135deg, #003F8C, #0051b4); transition: transform .25s, box-shadow .25s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,63,140,.35); }
        @media print {
            body { background: white !important; }
            .no-print { display: none !important; }
            .print-card { background: white !important; border: 1px solid #ddd !important; box-shadow: none !important; color: black !important; }
        }
    </style>
<?php if (defined('HCAPTCHA_SITE_KEY') && HCAPTCHA_SITE_KEY !== ''): ?>
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
<?php endif; ?>
</head>
<body class="min-h-screen flex flex-col">

<!-- HEADER -->
<header class="no-print fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-3.5 flex items-center justify-between">
        <a href="../index.html" class="flex items-center group" aria-label="SSOMA SAFE — Ir al inicio">
            <img src="../assets/img/logo-ssoma.png" alt="SSOMA SAFE" class="h-14 w-auto object-contain group-hover:scale-105 transition-transform" width="85" height="56">
        </a>
        <a href="../index.html" class="text-brand-dark/60 hover:text-brand-primary text-sm flex items-center gap-1.5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Inicio
        </a>
    </div>
</header>

<main class="flex-grow pt-20">

<?php if ($success): ?>
<!-- ════ VISTA ÉXITO ════ -->
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="print-card card p-8 sm:p-12 border border-emerald-100">
        <div class="no-print text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 mb-5">
                <svg class="w-9 h-9" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h1 class="font-display font-extrabold text-3xl text-brand-dark mb-2">¡Reclamación Registrada!</h1>
            <p class="text-gray-500 text-sm max-w-lg mx-auto">
                Tu reclamo fue procesado. Se envió un cargo al correo <strong class="text-brand-dark"><?= esc($submittedData['email']) ?></strong>.
            </p>
        </div>

        <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 print-card">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-200 pb-5 mb-6">
                <div>
                    <h2 class="font-display font-extrabold text-xl text-brand-dark">HOJA DE RECLAMACIÓN VIRTUAL</h2>
                    <p class="text-xs text-gray-400 mt-1">Conforme a la Ley N° 29571 / D.S. N° 011-2011-PCM</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:text-right">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Código</p>
                    <p class="text-xl font-extrabold text-emerald-600 font-display mt-0.5"><?= esc($submittedData['codigo']) ?></p>
                    <p class="text-xs text-gray-400 mt-1">Fecha: <?= esc($submittedData['fecha']) ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-500 border-b border-gray-100 pb-4 mb-4">
                <div><span class="block font-bold text-gray-700 mb-0.5">Proveedor:</span><?= EMPRESA_RAZON_SOCIAL ?></div>
                <div><span class="block font-bold text-gray-700 mb-0.5">RUC:</span><?= EMPRESA_RUC_PUBLICO ?></div>
                <div class="md:col-span-2"><span class="block font-bold text-gray-700 mb-0.5">Domicilio Fiscal:</span><?= EMPRESA_DIRECCION ?></div>
            </div>

            <?php foreach ([
                ['1. Identificación del Consumidor Reclamante', [
                    ['Nombre:', '<strong>' . esc($submittedData['nombres']) . '</strong>'],
                    ['Documento:', esc($submittedData['doc_tipo']) . ' — ' . esc($submittedData['doc_nro'])],
                    ['Domicilio:', esc($submittedData['direccion']) . ', ' . esc($submittedData['distrito']) . ' — ' . esc($submittedData['provincia']) . ' (' . esc($submittedData['departamento']) . ')'],
                    ['Contacto:', 'Email: ' . esc($submittedData['email']) . ' | Tel: ' . esc($submittedData['telefono'] ?: '—')],
                ]],
                ['2. Identificación del Bien Contratado', [
                    ['Sede:', esc($submittedData['sede'])],
                    ['Servicio / Producto:', esc($submittedData['servicio'])],
                    ['Tipo de bien:', '<span class="capitalize">' . esc($submittedData['bien_tipo']) . '</span>'],
                    ['Monto reclamado:', 'S/. ' . esc($submittedData['monto'])],
                ]],
            ] as [$title, $fields]): ?>
            <h3 class="text-xs font-bold uppercase tracking-wider text-brand-primary mb-3 mt-4"><?= $title ?></h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4 text-xs text-gray-600 border-b border-gray-100 pb-4">
                <?php foreach ($fields as [$label, $val]): ?>
                <div><span class="block text-gray-400 mb-0.5"><?= $label ?></span><span><?= $val ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <?php if (!empty($submittedData['menor_edad'])): ?>
            <div class="mb-4 bg-blue-50 p-3 rounded-lg border border-blue-100 text-xs">
                <span class="block font-bold text-gray-700 mb-0.5">Apoderado:</span>
                <span><?= esc($submittedData['apoderado_nombres']) ?> (<?= esc($submittedData['apoderado_doc_tipo']) ?> <?= esc($submittedData['apoderado_doc_nro']) ?>)</span>
            </div>
            <?php endif; ?>

            <h3 class="text-xs font-bold uppercase tracking-wider text-brand-primary mb-3 mt-4">3. Detalle de la Reclamación y Pedido</h3>
            <div class="space-y-3 text-xs text-gray-600">
                <div class="flex items-center gap-2">
                    <span class="text-gray-400">Tipo:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider <?= $submittedData['reclamo_tipo']==='reclamo'?'bg-red-50 text-red-600 border border-red-200':'bg-amber-50 text-amber-600 border border-amber-200' ?>"><?= esc($submittedData['reclamo_tipo']) ?></span>
                </div>
                <div>
                    <span class="block text-gray-400 mb-1">Detalle:</span>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-wrap text-gray-700"><?= esc($submittedData['detalle']) ?></div>
                </div>
                <div>
                    <span class="block text-gray-400 mb-1">Pedido:</span>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-100 whitespace-pre-wrap text-gray-700"><?= esc($submittedData['pedido']) ?></div>
                </div>
            </div>
        </div>

        <div class="no-print mt-8 flex flex-col sm:flex-row justify-center gap-4">
            <button onclick="window.print()" class="btn-primary text-white font-bold px-6 py-3.5 rounded-xl text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Descargar / Imprimir
            </button>
            <a href="../index.html" class="border border-gray-200 text-brand-dark font-semibold px-6 py-3.5 rounded-xl text-sm flex items-center justify-center gap-2 hover:bg-gray-50 transition-colors">
                Volver al inicio
            </a>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ════ HERO ════ -->
<section aria-labelledby="lr-title">
    <div style="background:linear-gradient(135deg,#001f4d 0%,#003F8C 55%,#004db5 100%)" class="relative overflow-hidden pb-14 pt-14">
        <div class="relative z-10 max-w-4xl mx-auto px-5 lg:px-8 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full mb-6 text-xs font-bold" style="background:rgba(200,16,46,.15);border:1.5px solid rgba(200,16,46,.35);color:#ff6b7a;">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Conforme al Código de Protección al Consumidor — INDECOPI
            </div>
            <h1 id="lr-title" class="font-display font-extrabold text-4xl sm:text-5xl text-white leading-tight mb-4">
                Libro de <span style="color:#7FB4E5">Reclamaciones</span>
            </h1>
            <p class="text-white/70 text-lg max-w-2xl mx-auto mb-10">
                Tu derecho como consumidor. Respondemos en máximo <strong class="text-white">30 días calendario</strong> según la Ley N.° 29571.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
                <?php foreach ([
                    ['Respuesta en 30 días', 'Plazo legal establecido por INDECOPI', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['100% Confidencial', 'Protegido por la Ley N.° 29733', 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                    ['Registro inmediato', 'Confirmación al instante por email', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                ] as [$t, $s, $path]): ?>
                <div style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.13);border-radius:1rem;padding:1.1rem;">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-2.5" style="background:rgba(127,180,229,.2);">
                        <svg class="w-4 h-4" style="color:#7FB4E5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $path ?>"/></svg>
                    </div>
                    <p class="text-white font-semibold text-sm mb-0.5"><?= $t ?></p>
                    <p class="text-xs" style="color:rgba(255,255,255,.45)"><?= $s ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ════ FORMULARIO ════ -->
<section class="py-12 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto">

        <?php if (!empty($errorMsg)): ?>
        <div class="no-print bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-xl mb-6 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <?= esc($errorMsg) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Formulario principal -->
            <div class="lg:col-span-2">
                <div class="card overflow-hidden">
                    <!-- Header formulario -->
                    <div style="background:linear-gradient(135deg,#003F8C,#0051b4)" class="p-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,.15)">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <h2 class="text-white font-bold text-sm font-display">Registro de Reclamación</h2>
                                    <p class="text-white/50 text-xs mt-0.5">SSOMA SAFE S.A.C. — <?= date('d/m/Y') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cuerpo del formulario -->
                    <div class="p-6 sm:p-8">
                        <form method="POST" action="libro-de-reclamaciones.php" class="space-y-6">
                            <input type="hidden" name="csrf_token" value="<?= esc($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="form_ts" value="<?= esc(reclamosTokenTiempo($recordsDir, (string)($_SESSION['csrf_token'] ?? ''))) ?>">
                            <div style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
                                <label for="hp_website">Sitio web (no llenar)</label>
                                <input type="text" name="hp_website" id="hp_website" tabindex="-1" autocomplete="off" value="">
                            </div>

                            <!-- ── SECCIÓN 1: Consumidor ── -->
                            <div class="border-b border-gray-100 pb-6">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center text-white text-xs font-black">1</div>
                                    <h3 class="font-display font-bold text-brand-dark">Identificación del Consumidor</h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Nombres y Apellidos completos <span class="text-red-500">*</span></label>
                                        <input type="text" name="nombres" required placeholder="Ingresa tus nombres completos" class="form-input" value="<?= isset($_POST['nombres'])?esc($_POST['nombres']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Tipo de Documento <span class="text-red-500">*</span></label>
                                        <select name="doc_tipo" required class="form-input">
                                            <option value="DNI"       <?= (($_POST['doc_tipo']??'')==='DNI')?'selected':'' ?>>DNI (Perú)</option>
                                            <option value="CE"        <?= (($_POST['doc_tipo']??'')==='CE')?'selected':'' ?>>Carnet de Extranjería</option>
                                            <option value="PASAPORTE" <?= (($_POST['doc_tipo']??'')==='PASAPORTE')?'selected':'' ?>>Pasaporte</option>
                                            <option value="RUC"       <?= (($_POST['doc_tipo']??'')==='RUC')?'selected':'' ?>>RUC</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">N° de Documento <span class="text-red-500">*</span></label>
                                        <input type="text" name="doc_nro" required placeholder="Número de documento" class="form-input" value="<?= isset($_POST['doc_nro'])?esc($_POST['doc_nro']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Correo Electrónico <span class="text-red-500">*</span></label>
                                        <input type="email" name="email" required placeholder="nombre@correo.com" class="form-input" value="<?= isset($_POST['email'])?esc($_POST['email']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Teléfono / Celular</label>
                                        <input type="tel" name="telefono" placeholder="9XX XXX XXX" class="form-input" value="<?= isset($_POST['telefono'])?esc($_POST['telefono']):'' ?>">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Dirección completa <span class="text-red-500">*</span></label>
                                        <input type="text" name="direccion" required placeholder="Av., Calle, N°, Dpto., Urb." class="form-input" value="<?= isset($_POST['direccion'])?esc($_POST['direccion']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Departamento <span class="text-red-500">*</span></label>
                                        <input type="text" name="departamento" required placeholder="Ej: Lima" class="form-input" value="<?= isset($_POST['departamento'])?esc($_POST['departamento']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Provincia <span class="text-red-500">*</span></label>
                                        <input type="text" name="provincia" required placeholder="Ej: Lima" class="form-input" value="<?= isset($_POST['provincia'])?esc($_POST['provincia']):'' ?>">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Distrito <span class="text-red-500">*</span></label>
                                        <input type="text" name="distrito" required placeholder="Ej: Miraflores" class="form-input" value="<?= isset($_POST['distrito'])?esc($_POST['distrito']):'' ?>">
                                    </div>
                                </div>
                                <!-- Menor de edad -->
                                <div class="mt-4 bg-blue-50 p-4 rounded-xl border border-blue-100">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" id="menor_edad" name="menor_edad" class="w-4 h-4 rounded flex-shrink-0 cursor-pointer" style="accent-color:#003F8C" onclick="toggleApoderado()" <?= isset($_POST['menor_edad'])?'checked':'' ?>>
                                        <span class="text-gray-600 text-xs">Soy menor de edad (se requiere ingresar los datos de un tutor/apoderado).</span>
                                    </label>
                                    <div id="apoderado_fields" class="mt-4 pt-4 border-t border-blue-200 grid grid-cols-1 sm:grid-cols-2 gap-4 hidden">
                                        <div class="sm:col-span-2">
                                            <label class="form-label">Nombres del Apoderado <span class="text-red-500">*</span></label>
                                            <input type="text" id="apoderado_nombres" name="apoderado_nombres" placeholder="Nombres del padre, madre o apoderado" class="form-input" value="<?= isset($_POST['apoderado_nombres'])?esc($_POST['apoderado_nombres']):'' ?>">
                                        </div>
                                        <div>
                                            <label class="form-label">Tipo Doc. Apoderado <span class="text-red-500">*</span></label>
                                            <select id="apoderado_doc_tipo" name="apoderado_doc_tipo" class="form-input">
                                                <option value="DNI">DNI</option>
                                                <option value="CE">Carnet de Extranjería</option>
                                                <option value="PASAPORTE">Pasaporte</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">N° Doc. Apoderado <span class="text-red-500">*</span></label>
                                            <input type="text" id="apoderado_doc_nro" name="apoderado_doc_nro" placeholder="N° de documento" class="form-input" value="<?= isset($_POST['apoderado_doc_nro'])?esc($_POST['apoderado_doc_nro']):'' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ── SECCIÓN 2: Bien contratado ── -->
                            <div class="border-b border-gray-100 pb-6">
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center text-white text-xs font-black">2</div>
                                    <h3 class="font-display font-bold text-brand-dark">Identificación del Bien Contratado</h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Sede / Establecimiento <span class="text-red-500">*</span></label>
                                        <select name="sede" required class="form-input">
                                            <option value="">Selecciona la sede</option>
                                            <option value="Lima — Av. República de Chile 324"    <?= (($_POST['sede']??'')==='Lima — Av. República de Chile 324')?'selected':'' ?>>Lima — Av. República de Chile 324</option>
                                            <option value="Chiclayo — Lapoint 1221"               <?= (($_POST['sede']??'')==='Chiclayo — Lapoint 1221')?'selected':'' ?>>Chiclayo — Lapoint 1221</option>
                                            <option value="Servicio online / plataforma digital"  <?= (($_POST['sede']??'')==='Servicio online / plataforma digital')?'selected':'' ?>>Servicio online / plataforma digital</option>
                                        </select>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="form-label">Servicio o Producto involucrado <span class="text-red-500">*</span></label>
                                        <input type="text" name="servicio" required placeholder="Ej: Consultoría SST, EPP certificado, Capacitación IPERC..." class="form-input" value="<?= isset($_POST['servicio'])?esc($_POST['servicio']):'' ?>">
                                    </div>
                                    <div>
                                        <label class="form-label">Tipo de bien <span class="text-red-500">*</span></label>
                                        <div class="flex items-center gap-6 mt-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="bien_tipo" value="servicio" required class="w-4 h-4" style="accent-color:#003F8C" <?= (!isset($_POST['bien_tipo'])||$_POST['bien_tipo']==='servicio')?'checked':'' ?>>
                                                <span class="text-gray-700 text-sm">Servicio</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" name="bien_tipo" value="producto" class="w-4 h-4" style="accent-color:#003F8C" <?= (($_POST['bien_tipo']??'')==='producto')?'checked':'' ?>>
                                                <span class="text-gray-700 text-sm">Producto</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Monto reclamado S/. <span class="text-gray-400 font-normal text-xs">(opcional)</span></label>
                                        <input type="number" step="0.01" min="0" name="monto" placeholder="0.00" class="form-input" value="<?= isset($_POST['monto'])?esc($_POST['monto']):'' ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- ── SECCIÓN 3: Detalle y pedido ── -->
                            <div>
                                <div class="flex items-center gap-3 mb-5">
                                    <div class="w-8 h-8 rounded-lg bg-brand-primary flex items-center justify-center text-white text-xs font-black">3</div>
                                    <h3 class="font-display font-bold text-brand-dark">Detalle del Reclamo y Pedido</h3>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="form-label">Tipo de reclamación <span class="text-red-500">*</span></label>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                            <label class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-colors">
                                                <input type="radio" name="reclamo_tipo" value="reclamo" required class="w-4 h-4 mt-0.5 flex-shrink-0" style="accent-color:#003F8C" <?= (!isset($_POST['reclamo_tipo'])||$_POST['reclamo_tipo']==='reclamo')?'checked':'' ?>>
                                                <div>
                                                    <span class="block text-brand-dark text-xs font-bold uppercase tracking-wider">Reclamo</span>
                                                    <span class="text-gray-500 text-xs leading-tight block mt-0.5">Disconformidad con los productos o servicios contratados.</span>
                                                </div>
                                            </label>
                                            <label class="flex items-start gap-2.5 p-3 rounded-xl bg-gray-50 border border-gray-100 cursor-pointer hover:bg-amber-50 hover:border-amber-200 transition-colors">
                                                <input type="radio" name="reclamo_tipo" value="queja" class="w-4 h-4 mt-0.5 flex-shrink-0" style="accent-color:#003F8C" <?= (($_POST['reclamo_tipo']??'')==='queja')?'checked':'' ?>>
                                                <div>
                                                    <span class="block text-brand-dark text-xs font-bold uppercase tracking-wider">Queja</span>
                                                    <span class="text-gray-500 text-xs leading-tight block mt-0.5">Disconformidad con la atención al cliente o instalaciones.</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label">Descripción detallada de tu queja o reclamo <span class="text-red-500">*</span></label>
                                        <textarea rows="5" name="detalle" required placeholder="Describe de forma detallada y ordenada lo ocurrido: fechas, circunstancias, personas involucradas..." class="form-input resize-none"><?= isset($_POST['detalle'])?esc($_POST['detalle']):'' ?></textarea>
                                    </div>
                                    <div>
                                        <label class="form-label">Pedido concreto — ¿Qué solicitas? <span class="text-red-500">*</span></label>
                                        <textarea rows="3" name="pedido" required placeholder="Indica tu solicitud: devolución, cambio, compensación, etc." class="form-input resize-none"><?= isset($_POST['pedido'])?esc($_POST['pedido']):'' ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Declaraciones -->
                            <div class="pt-4 border-t border-gray-100 space-y-3">
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded flex-shrink-0 cursor-pointer" style="accent-color:#003F8C">
                                    <span class="text-gray-500 text-xs leading-relaxed">Declaro ser el usuario titular y que los datos consignados son reales y verdaderos.</span>
                                </label>
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" required class="w-4 h-4 mt-0.5 rounded flex-shrink-0 cursor-pointer" style="accent-color:#003F8C">
                                    <span class="text-gray-500 text-xs leading-relaxed">Acepto el tratamiento de mis datos personales conforme a la <strong class="text-gray-700">Ley N.° 29733</strong> y la Política de Privacidad de SSOMA SAFE.</span>
                                </label>
                            </div>

                            <?php if (defined('HCAPTCHA_SITE_KEY') && HCAPTCHA_SITE_KEY !== ''): ?>
                            <div class="flex justify-center pt-2">
                                <div class="h-captcha" data-sitekey="<?= esc(HCAPTCHA_SITE_KEY) ?>"></div>
                            </div>
                            <?php endif; ?>

                            <button type="submit" class="btn-primary w-full py-4 rounded-xl text-white font-bold text-sm tracking-wide flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                Presentar Reclamación
                            </button>
                            <p class="text-center text-xs text-gray-400 leading-relaxed">
                                Al enviar, SSOMA SAFE registrará tu reclamación conforme a la Ley N.° 29571.
                                Respuesta máxima en <strong class="text-gray-500">30 días calendario</strong>.
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Datos proveedor -->
                <div class="card p-6 text-xs space-y-3">
                    <h3 class="text-sm font-bold text-brand-dark uppercase tracking-wider mb-2">Datos del Proveedor</h3>
                    <div>
                        <span class="block text-gray-400 uppercase tracking-widest text-[9px] mb-0.5">Razón Social</span>
                        <strong class="text-brand-dark text-sm"><?= EMPRESA_RAZON_SOCIAL ?></strong>
                    </div>
                    <div>
                        <span class="block text-gray-400 uppercase tracking-widest text-[9px] mb-0.5">RUC</span>
                        <strong class="text-brand-dark"><?= EMPRESA_RUC_PUBLICO ?></strong>
                    </div>
                    <div>
                        <span class="block text-gray-400 uppercase tracking-widest text-[9px] mb-0.5">Dirección</span>
                        <span class="text-gray-600"><?= EMPRESA_DIRECCION ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-400 uppercase tracking-widest text-[9px] mb-0.5">Contacto</span>
                        <a href="mailto:<?= EMPRESA_EMAIL ?>" class="text-brand-primary hover:underline"><?= EMPRESA_EMAIL ?></a>
                    </div>
                </div>

                <!-- Aviso INDECOPI -->
                <div class="card p-6">
                    <h3 class="text-sm font-bold text-brand-dark uppercase tracking-wider mb-3">Aviso Virtual</h3>
                    <p class="text-gray-500 text-xs leading-relaxed mb-4">
                        Conforme al Código de Protección y Defensa del Consumidor, contamos con Libro de Reclamaciones Virtual.
                    </p>
                    <div class="relative rounded-xl overflow-hidden border border-gray-100 group cursor-pointer" onclick="openNoticeModal()">
                        <img src="AvisoVirtual_page1.png" alt="Aviso Virtual INDECOPI" class="w-full h-auto object-cover opacity-80 group-hover:opacity-100 transition-opacity">
                        <div class="absolute inset-0 bg-brand-primary/30 flex items-center justify-center group-hover:bg-brand-primary/10 transition-all">
                            <span class="bg-white text-brand-primary font-bold px-3 py-1.5 rounded-lg text-xs shadow-lg">Ver a pantalla completa</span>
                        </div>
                    </div>
                </div>

                <!-- Diferencia queja/reclamo -->
                <div class="card p-6 space-y-3">
                    <h3 class="text-sm font-bold text-brand-dark uppercase tracking-wider mb-2">¿Cuál es la diferencia?</h3>
                    <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                        <p class="text-brand-primary font-bold text-xs mb-1">RECLAMO</p>
                        <p class="text-gray-600 text-xs">Disconformidad con los <strong>productos o servicios</strong> adquiridos.</p>
                    </div>
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-100">
                        <p class="text-amber-700 font-bold text-xs mb-1">QUEJA</p>
                        <p class="text-gray-600 text-xs">Disconformidad con la <strong>atención al cliente</strong> o instalaciones.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

</main>

<!-- FOOTER -->
<footer class="no-print bg-brand-dark py-10 mt-8">
    <div class="max-w-7xl mx-auto px-6 text-center">
        <p class="text-gray-400 text-sm">© <?= date('Y') ?> <?= EMPRESA_RAZON_SOCIAL ?> · RUC <?= EMPRESA_RUC_PUBLICO ?> · Todos los derechos reservados.</p>
        <p class="text-gray-600 text-xs mt-1">Regulado por INDECOPI · Ley de Protección de Datos Personales N.° 29733</p>
    </div>
</footer>

<!-- Modal Aviso INDECOPI -->
<div id="notice-modal" class="no-print fixed inset-0 z-[100] hidden items-center justify-center p-4" style="background:rgba(0,0,0,.85)">
    <div class="absolute inset-0 cursor-pointer" onclick="closeNoticeModal()"></div>
    <div class="relative z-10 max-w-lg w-full bg-white rounded-2xl p-4 shadow-2xl flex flex-col items-center">
        <button onclick="closeNoticeModal()" class="absolute -top-10 right-0 text-white hover:text-brand-light text-3xl font-light cursor-pointer">✕</button>
        <div class="w-full overflow-y-auto max-h-[80vh] border border-gray-200 rounded-xl">
            <img src="AvisoVirtual_page1.png" alt="Aviso Virtual Oficial INDECOPI" class="w-full h-auto">
        </div>
        <p class="text-gray-400 text-xs mt-3 text-center">Aviso oficial de disponibilidad de Libro de Reclamaciones — INDECOPI.</p>
    </div>
</div>

<script>
function toggleApoderado() {
    const cb = document.getElementById('menor_edad');
    const f  = document.getElementById('apoderado_fields');
    const n  = document.getElementById('apoderado_nombres');
    const d  = document.getElementById('apoderado_doc_nro');
    if (cb.checked) { f.classList.remove('hidden'); n.required = true; d.required = true; }
    else            { f.classList.add('hidden');    n.required = false; d.required = false; n.value = ''; d.value = ''; }
}
window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('menor_edad')) toggleApoderado();
    <?php if (!empty($errorMsg)): ?>
    if (typeof hcaptcha !== 'undefined') { hcaptcha.reset(); }
    <?php endif; ?>
});
function openNoticeModal()  { const m = document.getElementById('notice-modal'); m.classList.remove('hidden'); m.classList.add('flex'); }
function closeNoticeModal() { const m = document.getElementById('notice-modal'); m.classList.add('hidden');    m.classList.remove('flex'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNoticeModal(); });
</script>
</body>
</html>
