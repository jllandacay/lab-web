<?php
require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function responder($datos,int $codigo=200): never { http_response_code($codigo);echo json_encode($datos,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit; }
set_exception_handler(function(Throwable $error){error_log($error->getMessage());$codigo=http_response_code();if($codigo<400)$codigo=$error instanceof RuntimeException?400:500;responder(['error'=>$error instanceof RuntimeException?$error->getMessage():'No se pudo completar la operación.'],$codigo);});
function api_login(): void {if(!alumno()) responder(['error'=>'Inicia sesión.'],401);}
function cuerpo(): array { $datos=json_decode(file_get_contents('php://input'),true);return is_array($datos)?$datos:$_POST; }
function metodo(array $permitidos): void {if(!in_array($_SERVER['REQUEST_METHOD'],$permitidos)){header('Allow: '.implode(', ',$permitidos));responder(['error'=>'Método no permitido.'],405);}}
