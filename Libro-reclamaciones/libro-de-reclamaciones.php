<?php
/**
 * Libro de Reclamaciones Virtual - SSOMA SAFE
 * Conforme a la Ley N° 29571 (Código de Protección y Defensa del Consumidor)
 * y el D.S. N° 011-2011-PCM (modificado por D.S. 101-2021-PCM).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => isset($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict']);
    session_start();
}
date_default_timezone_set('America/Lima');

$smtpConfigFile = __DIR__ . '/../config.smtp.php';
if (file_exists($smtpConfigFile)) require $smtpConfigFile;

if (!defined('DOMINIO_PERMITIDO'))    define('DOMINIO_PERMITIDO',    'ssomasafe.com');
if (!defined('EMPRESA_RAZON_SOCIAL')) define('EMPRESA_RAZON_SOCIAL', 'SSOMA SAFE S.A.C.');
if (!defined('EMPRESA_RUC'))          define('EMPRESA_RUC',          'XXXXXXXXXX');
if (!defined('EMPRESA_DIRECCION'))    define('EMPRESA_DIRECCION',    'Av. República de Chile 324, Lima / Lapoint 1221, Chiclayo');
if (!defined('EMPRESA_EMAIL'))        define('EMPRESA_EMAIL',        'info@ssomasafe.com');
if (!defined('SMTP_PORT'))            define('SMTP_PORT',            465);
if (!defined('SMTP_FROM_NAME'))       define('SMTP_FROM_NAME',       'SSOMA SAFE');
if (!defined('EMPRESA_NOTIF_EMAIL'))  define('EMPRESA_NOTIF_EMAIL',  'reclamaciones@ssomasafe.com');
if (!defined('RATE_LIMIT_MAX'))       define('RATE_LIMIT_MAX',       1);
if (!defined('RATE_LIMIT_WINDOW'))    define('RATE_LIMIT_WINDOW',    1800);
if (!defined('HCAPTCHA_SITE_KEY'))    define('HCAPTCHA_SITE_KEY',    '');
if (!defined('HCAPTCHA_SECRET_KEY'))  define('HCAPTCHA_SECRET_KEY',  '');

$recordsDir  = __DIR__ . '/reclamaciones';
$recordsFile = $recordsDir . '/records.json';
$lockFile    = $recordsDir . '/records.lock';

if (!is_dir($recordsDir)) {
    if (!@mkdir($recordsDir, 0755, true))
        $errorMsg = 'Error de configuración del servidor.';
}

$rateFile     = $recordsDir . '/rate_limits.json';
$logFile      = $recordsDir . '/security.log';
$htaccessPath = $recordsDir . '/.htaccess';
if (!file_exists($htaccessPath)) {
    @file_put_contents($htaccessPath, "Order Deny,Allow\nDeny from all\n");
}

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
    $pdf->MultiCell(0, 5, $c('Razon Social: ' . EMPRESA_RAZON_SOCIAL . '   RUC: ' . EMPRESA_RUC), 0, 'L');
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

// ── Enviar correo SMTP ────────────────────────────────────────────────────────
function enviarCorreoSMTP(string $toEmail, string $subject, string $htmlBody, string $pdfB64 = '', string $pdfName = '', string &$err = ''): bool {
    if (!defined('SMTP_HOST') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
        $err = 'Configuración SMTP no encontrada.'; return false;
    }
    $toEmail = sanitizarCabecera($toEmail);
    $subject = sanitizarCabecera($subject);
    $enc     = fn(string $s): string => '=?UTF-8?B?' . base64_encode($s) . '?=';
    $from    = SMTP_USER;
    $eol     = "\r\n";
    $headers  = 'Date: ' . date('r') . $eol;
    $headers .= 'From: ' . $enc(SMTP_FROM_NAME) . ' <' . $from . '>' . $eol;
    $headers .= 'To: <' . $toEmail . '>' . $eol;
    $headers .= 'Subject: ' . $enc($subject) . $eol;
    $headers .= 'Reply-To: ' . EMPRESA_EMAIL . $eol;
    $headers .= 'MIME-Version: 1.0' . $eol;
    if ($pdfB64) {
        $b = '----=_Part_' . md5(uniqid());
        $headers .= 'Content-Type: multipart/mixed; boundary="' . $b . '"' . $eol;
        $body  = '--' . $b . $eol . 'Content-Type: text/html; charset=UTF-8' . $eol . 'Content-Transfer-Encoding: 7bit' . $eol . $eol . $htmlBody . $eol;
        $body .= '--' . $b . $eol . 'Content-Type: application/pdf; name="' . $pdfName . '"' . $eol . 'Content-Transfer-Encoding: base64' . $eol . 'Content-Disposition: attachment; filename="' . $pdfName . '"' . $eol . $eol . $pdfB64 . $eol;
        $body .= '--' . $b . '--';
    } else {
        $headers .= 'Content-Type: text/html; charset=UTF-8' . $eol;
        $body = $htmlBody;
    }
    $message = str_replace($eol . '.', $eol . '..', str_replace("\n", $eol, str_replace(["\r\n","\r","\n"], "\n", $headers . $eol . $body)));
    $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
    $fp  = @stream_socket_client((SMTP_PORT == 465 ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $err = "Conexión SMTP fallida: $errstr ($errno)"; return false; }
    stream_set_timeout($fp, 20);
    $read = function() use ($fp): string { $d=''; while(($l=fgets($fp,515))!==false){$d.=$l;if(strlen($l)<4||$l[3]===' ')break;} return $d; };
    $cmd  = function(string $c) use ($fp, $read): string { fwrite($fp, $c . "\r\n"); return $read(); };
    $ok   = function(string $r, array $codes) use (&$err): bool { foreach($codes as $c){if(strncmp($r,$c,strlen($c))===0)return true;} $err=trim($r);return false; };
    $fail = function() use ($fp) { @fwrite($fp,"QUIT\r\n"); @fclose($fp); return false; };

    if (!$ok($read(),                            ['220'])) return $fail();
    if (!$ok($cmd('EHLO '.SMTP_HOST),            ['250'])) return $fail();
    if (!$ok($cmd('AUTH LOGIN'),                  ['334'])) return $fail();
    if (!$ok($cmd(base64_encode(SMTP_USER)),      ['334'])) return $fail();
    if (!$ok($cmd(base64_encode(SMTP_PASS)),      ['235'])) return $fail();
    if (!$ok($cmd('MAIL FROM:<'.$from.'>'),       ['250'])) return $fail();
    if (!$ok($cmd('RCPT TO:<'.$toEmail.'>'), ['250','251'])) return $fail();
    if (!$ok($cmd('DATA'),                        ['354'])) return $fail();
    if (!$ok($cmd($message."\r\n."),              ['250'])) return $fail();
    $cmd('QUIT'); fclose($fp);
    return true;
}

// ── Funciones de seguridad ────────────────────────────────────────────────────
function sanitizarCabecera(string $s): string {
    return trim(str_replace(["\r\n","\r","\n","%0d%0a","%0D%0A","%0d","%0D","%0a","%0A"], '', $s));
}
function esc(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
function limpiarTexto(?string $s, int $maxLen = 500): string {
    if ($s === null || $s === '') return '';
    return mb_substr(trim(strip_tags($s)), 0, $maxLen, 'UTF-8');
}
function checkAndRegisterRateLimit(string $ip, string $rateFile): bool {
    $lockPath = $rateFile . '.lock';
    $fp = @fopen($lockPath, 'c');
    if (!$fp || !flock($fp, LOCK_EX)) return true;
    $now = time(); $window = RATE_LIMIT_WINDOW; $max = RATE_LIMIT_MAX; $data = [];
    if (file_exists($rateFile)) {
        $raw = file_get_contents($rateFile);
        $data = ($raw !== false && $raw !== '') ? (json_decode($raw, true) ?: []) : [];
    }
    foreach (array_keys($data) as $k) {
        $data[$k] = array_values(array_filter($data[$k], function($ts) use ($now, $window) { return ($now - $ts) < $window; }));
        if (empty($data[$k])) unset($data[$k]);
    }
    $ipEntries = $data[$ip] ?? [];
    if (count($ipEntries) >= $max) { flock($fp, LOCK_UN); fclose($fp); return false; }
    $ipEntries[] = $now; $data[$ip] = $ipEntries;
    file_put_contents($rateFile, json_encode($data));
    flock($fp, LOCK_UN); fclose($fp);
    return true;
}
function logSecurityEvent(string $logFile, string $event, array $context = []): void {
    foreach (['email','nombres','doc_nro','apoderado_nombres','apoderado_doc_nro'] as $k) unset($context[$k]);
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' | ' . $event . ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
function verificarHCaptcha(string $token, string $secretKey): bool {
    if ($token === '' || $secretKey === '') return false;
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query(['secret' => $secretKey, 'response' => $token]), 'timeout' => 10]]);
    $result = @file_get_contents('https://hcaptcha.com/siteverify', false, $ctx);
    if ($result === false) return false;
    $data = json_decode($result, true);
    return isset($data['success']) && $data['success'] === true;
}

$success = false; $errorMsg = ''; $generatedCode = ''; $submittedData = [];
$clientIP = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')[0]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Honeypot
    if (!empty($_POST['hp_website'] ?? '')) {
        logSecurityEvent($logFile, 'HONEYPOT_TRIGGERED', ['ip' => $clientIP]);
        header('HTTP/1.1 200 OK'); exit;
    }

    // 2. CSRF
    $csrfPost = (string)($_POST['csrf_token'] ?? '');
    $csrfSess = (string)($_SESSION['csrf_token'] ?? '');
    if (!$csrfPost || !$csrfSess || !hash_equals($csrfSess, $csrfPost)) {
        logSecurityEvent($logFile, 'CSRF_FAILED', ['ip' => $clientIP]);
        $errorMsg = 'Token de seguridad inválido. Recarga la página e inténtalo de nuevo.';
    }

    // 3. hCaptcha (solo si está configurado)
    if (empty($errorMsg) && defined('HCAPTCHA_SECRET_KEY') && HCAPTCHA_SECRET_KEY !== '') {
        $captchaToken = (string)($_POST['h-captcha-response'] ?? '');
        if (!verificarHCaptcha($captchaToken, HCAPTCHA_SECRET_KEY)) {
            logSecurityEvent($logFile, 'CAPTCHA_FAILED', ['ip' => $clientIP]);
            $errorMsg = 'Por favor, completa el desafío de seguridad (captcha) antes de enviar.';
        }
    }

    // 4. Origin / Referer
    if (empty($errorMsg)) {
        $origin  = $_SERVER['HTTP_ORIGIN']  ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($origin && strpos($origin, DOMINIO_PERMITIDO) === false) {
            logSecurityEvent($logFile, 'ORIGIN_REJECTED', ['ip' => $clientIP, 'origin' => $origin]);
            $errorMsg = 'Solicitud rechazada por origen no permitido.';
        } elseif (!$origin && $referer && strpos($referer, DOMINIO_PERMITIDO) === false) {
            logSecurityEvent($logFile, 'REFERER_REJECTED', ['ip' => $clientIP]);
            $errorMsg = 'Solicitud rechazada.';
        }
    }

    // 5. Rate limiting
    if (empty($errorMsg)) {
        if (!checkAndRegisterRateLimit($clientIP, $rateFile)) {
            logSecurityEvent($logFile, 'RATE_LIMIT_EXCEEDED', ['ip' => $clientIP]);
            $errorMsg = 'Has enviado un reclamo recientemente. Por favor, espera 30 minutos antes de volver a intentarlo.';
        }
    }

    // 6. Sanitizar y validar
    if (empty($errorMsg)) {
        $nombres      = limpiarTexto($_POST['nombres']      ?? '', 200);
        $doc_tipo     = $_POST['doc_tipo']     ?? '';
        $doc_nro      = limpiarTexto($_POST['doc_nro']      ?? '', 20);
        $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
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
        $detalle      = limpiarTexto($_POST['detalle']      ?? '', 2000);
        $pedido       = limpiarTexto($_POST['pedido']       ?? '', 2000);
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
        enviarCorreoSMTP($email, 'Cargo de Hoja de Reclamación N° ' . $generatedCode . ' — SSOMA SAFE', $emailConsumidor, $pdfB64, $pdfName, $errC);

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
        enviarCorreoSMTP(EMPRESA_NOTIF_EMAIL, 'NUEVA RECLAMACIÓN N° ' . $generatedCode . ' — ' . $nombres, $emailEmpresa, $pdfB64, $pdfName, $errE);

        $_SESSION['csrf_token']      = bin2hex(random_bytes(32));
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
<header class="no-print fixed top-0 left-0 right-0 z-50 bg-brand-dark/96 backdrop-blur-xl border-b border-white/10 shadow-sm">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-3.5 flex items-center justify-between">
        <a href="../index.html" class="flex items-center group" aria-label="SSOMA SAFE — Ir al inicio">
            <div class="bg-white rounded-xl px-3 py-1.5 shadow group-hover:scale-105 transition-transform">
                <img src="../assets/img/logo-ssoma.jpg" alt="SSOMA SAFE" class="h-8 w-auto object-contain" width="110" height="32">
            </div>
        </a>
        <a href="../index.html" class="text-white/60 hover:text-white text-sm flex items-center gap-1.5 transition-colors">
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
                <div><span class="block font-bold text-gray-700 mb-0.5">RUC:</span><?= EMPRESA_RUC ?></div>
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
                        <strong class="text-brand-dark"><?= EMPRESA_RUC ?></strong>
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
        <p class="text-gray-400 text-sm">© <?= date('Y') ?> <?= EMPRESA_RAZON_SOCIAL ?> · RUC <?= EMPRESA_RUC ?> · Todos los derechos reservados.</p>
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
