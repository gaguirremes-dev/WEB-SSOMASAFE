<?php
/**
 * Plantilla de configuración — copia este archivo a config.smtp.php
 * y rellena tus valores reales.
 * config.smtp.php NO debe subirse al repositorio (.gitignore).
 */

// ── Empresa ───────────────────────────────────────────────────────────────────
define('DOMINIO_PERMITIDO',    'ssomasafe.com');
define('EMPRESA_RAZON_SOCIAL', 'SSOMA SAFE S.A.C.');
define('EMPRESA_RUC',          'TU_RUC_AQUI');
define('EMPRESA_DIRECCION',    'Av. República de Chile 324, Lima / Lapoint 1221, Chiclayo');
define('EMPRESA_EMAIL',        'info@ssomasafe.com');

// ── SMTP ──────────────────────────────────────────────────────────────────────
define('SMTP_HOST',           'mail.ssomasafe.com');
define('SMTP_PORT',           465);
define('SMTP_USER',           'reclamaciones@ssomasafe.com');
define('SMTP_PASS',           'TU_CONTRASEÑA_AQUI');
define('SMTP_FROM_NAME',      'SSOMA SAFE');
define('EMPRESA_NOTIF_EMAIL', 'reclamaciones@ssomasafe.com');

// ── Rate limiting ─────────────────────────────────────────────────────────────
define('RATE_LIMIT_MAX',    1);     // máx. envíos por IP
define('RATE_LIMIT_WINDOW', 1800);  // ventana en segundos (30 min)

// ── hCaptcha (https://dashboard.hcaptcha.com) ─────────────────────────────────
define('HCAPTCHA_SITE_KEY',   'TU_SITE_KEY_AQUI');
define('HCAPTCHA_SECRET_KEY', 'TU_SECRET_KEY_AQUI');
