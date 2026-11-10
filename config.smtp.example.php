<?php
/**
 * Plantilla de configuración — copia este archivo a config.smtp.php
 * y rellena tus valores reales.
 * config.smtp.php NO debe subirse al repositorio (.gitignore).
 */

// ── Empresa ───────────────────────────────────────────────────────────────────
define('DOMINIO_PERMITIDO',    'ssomasafe.com');
define('EMPRESA_RAZON_SOCIAL', 'SSOMA SAFE S.A.C.');
define('EMPRESA_RUC',          '20607018287');
define('EMPRESA_DIRECCION',    'Av. República de Chile 324, Lima / Lapoint 1221, Chiclayo');
define('EMPRESA_EMAIL',        'comercial@ssomasafe.com');

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

// ── Seguridad del Libro de Reclamaciones (TODO OPCIONAL) ─────────────────────
// Si no defines estas constantes se usan los valores por defecto que se muestran aquí.

// Sal para anonimizar las IP: solo se guarda un hash SHA-256 salado, nunca la IP.
// Déjala vacía y el sistema crea una clave aleatoria propia (reclamaciones/install.key).
define('IP_HASH_SALT', '');

// Límites anti-spam
define('RATE_LIMIT_MAX',      1);     // envíos por IP dentro de la ventana
define('RATE_LIMIT_WINDOW',   1800);  // ventana en segundos (1800 = 30 minutos)
define('RATE_LIMIT_DAY_MAX',  5);     // envíos por IP cada 24 horas
define('SITE_LIMIT_HOUR_MAX', 20);    // envíos de todo el sitio por hora
define('SITE_LIMIT_DAY_MAX',  80);    // envíos de todo el sitio por día

// Verificación del certificado TLS del servidor SMTP. Mantenlo en true.
// Ponlo en false SOLO si los correos dejan de enviarse porque el certificado del
// servidor no coincide con SMTP_HOST (sigue cifrado, pero no valida la identidad del servidor).
define('SMTP_VERIFY_TLS', true);
