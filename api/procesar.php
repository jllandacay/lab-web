<?php
require __DIR__.'/comun.php';metodo(['POST']);csrf();$nombre=trim($_POST['nombre']??'');
if($nombre===''||mb_strlen($nombre)>100) responder(['error'=>'Escribe un nombre de 1 a 100 caracteres.'],422);
responder(['pasos'=>['Cliente: JavaScript validó el formulario y envió POST.','Apache: entregó la petición a PHP.','PHP: recibió y validó el nombre.','PHP: construyó JSON.','Cliente: muestra esta respuesta.'],'recibido'=>['metodo'=>$_SERVER['REQUEST_METHOD'],'nombre'=>$nombre],'respuesta'=>'Hola, '.$nombre,'nota'=>'Esta operación no necesita una consulta MySQL.']);
