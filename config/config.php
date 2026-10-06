<?php
declare(strict_types=1);
const BASE_URL = '/lab-web';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'lab_web';
const DB_USER = 'root';
const DB_PASS = '';
date_default_timezone_set('America/Bogota');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf(): void {
    // El token evita que otro sitio envíe operaciones en nombre del alumno.
    if (!hash_equals(token(), $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) { http_response_code(403); throw new RuntimeException('Token CSRF inválido. Recarga la página.'); }
}
function alumno(): ?array { return $_SESSION['alumno'] ?? null; }
function exigir_login(): void { if (!alumno()) { header('Location: '.BASE_URL.'/login.php'); exit; } }
