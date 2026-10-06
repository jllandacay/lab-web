<?php
require __DIR__.'/comun.php';metodo(['POST']);csrf();$accion=$_POST['accion']??'';$valor=trim($_POST['valor']??'');if(mb_strlen($valor)>100) responder(['error'=>'Máximo 100 caracteres'],422);
if($accion==='crear'){setcookie('lab_demo',$valor,['expires'=>time()+3600,'path'=>BASE_URL,'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);$_SESSION['demo']=$valor;}
elseif($accion==='borrar'){setcookie('lab_demo','',['expires'=>time()-3600,'path'=>BASE_URL,'httponly'=>true,'samesite'=>'Lax']);unset($_SESSION['demo']);}
elseif($accion!=='leer')responder(['error'=>'Operación inválida'],422);
responder(['cookie_recibida'=>$_COOKIE['lab_demo']??null,'sesion_actual'=>$_SESSION['demo']??null,'nota'=>'Set-Cookie cambia la cookie del navegador; pulsa Leer para verla en la siguiente petición.']);
