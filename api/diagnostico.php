<?php
require_once __DIR__.'/../config/db.php';
function diagnostico(): array {
    $lista=[['PHP 8.x',PHP_VERSION,version_compare(PHP_VERSION,'8.0','>=')]];
    foreach(['pdo_mysql','mbstring','openssl','curl'] as $extension) $lista[]=[$extension,extension_loaded($extension)?'Disponible':'Ausente',extension_loaded($extension)];
    try { bd()->query('SELECT 1'); $lista[]=['MySQL','Conexión correcta',true]; } catch(Throwable $e) { $lista[]=['MySQL',$e->getMessage(),false]; }
    $apache=function_exists('apache_get_modules') ? in_array('mod_rewrite',apache_get_modules()) : null;
    $lista[]=['mod_rewrite',$apache===null?'No verificable desde CLI/FastCGI':($apache?'Activo':'Inactivo'),$apache];
    $lista[]=['Escritura del proyecto',is_writable(dirname(__DIR__))?'Permitida':'Denegada',is_writable(dirname(__DIR__))];
    $lista[]=['display_errors',ini_get('display_errors')?'Activo (solo desarrollo)':'Desactivado',!empty(ini_get('display_errors'))];
    $lista[]=['Zona horaria',date_default_timezone_get(),date_default_timezone_get()==='America/Bogota'];
    return $lista;
}
if(realpath($_SERVER['SCRIPT_FILENAME'] ?? '')===__FILE__) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(diagnostico(),JSON_UNESCAPED_UNICODE); }
