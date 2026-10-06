<?php
require __DIR__.'/comun.php';metodo(['GET','POST','PUT','DELETE']);
if($_SERVER['REQUEST_METHOD']!=='GET') csrf();
$estado=(int)($_GET['estado']??200);if(!in_array($estado,[200,201,400,404,500])) responder(['error'=>'Estado no permitido'],422);
// Lista permitida: no exponer Authorization, cookies de sesión ni datos internos del servidor.
$cabeceras=function_exists('getallheaders')?getallheaders():[];$seguras=[];foreach($cabeceras as $k=>$v) if(in_array(strtolower($k),['accept','content-type','user-agent','host']))$seguras[$k]=$v;
$cookies=array_intersect_key($_COOKIE,['lab_demo'=>true]);
responder(['SERVER'=>array_intersect_key($_SERVER,array_flip(['REQUEST_METHOD','REQUEST_URI','SERVER_PROTOCOL','HTTPS'])),'cabeceras'=>$seguras,'GET'=>$_GET,'POST'=>array_diff_key($_POST,['csrf'=>true]),'cuerpo'=>$_SERVER['REQUEST_METHOD']==='GET'?null:cuerpo(),'COOKIE'=>$cookies,'estado_simulado'=>$estado,'nota'=>'Los estados de error son intencionales en este probador.'], $estado);
