<?php
require __DIR__.'/comun.php';metodo(['GET','POST','PUT','DELETE']);api_login();$usuario=alumno()['id'];$metodo=$_SERVER['REQUEST_METHOD'];
// La condición alumno_id impide consultar o modificar tareas de otro alumno.
if($metodo==='GET'){$q=bd()->prepare('SELECT id,titulo,completada,creada_en FROM tareas WHERE alumno_id=? ORDER BY id DESC');$q->execute([$usuario]);responder($q->fetchAll());}
csrf();$datos=cuerpo();$id=filter_var($datos['id']??null,FILTER_VALIDATE_INT);
if($metodo==='POST'||$metodo==='PUT'){$titulo=trim($datos['titulo']??'');if($titulo===''||mb_strlen($titulo)>150)responder(['error'=>'Título de 1 a 150 caracteres.'],422);}
if($metodo==='POST'){$q=bd()->prepare('INSERT INTO tareas(alumno_id,titulo) VALUES(?,?)');$q->execute([$usuario,$titulo]);responder(['id'=>bd()->lastInsertId()],201);}
if(!$id||$id<1)responder(['error'=>'ID inválido'],422);
$q=bd()->prepare('SELECT id FROM tareas WHERE id=? AND alumno_id=?');$q->execute([$id,$usuario]);if(!$q->fetch())responder(['error'=>'Tarea no encontrada'],404);
if($metodo==='PUT'){if(!isset($datos['completada'])||!in_array($datos['completada'],[0,1,true,false],true))responder(['error'=>'Estado inválido'],422);$q=bd()->prepare('UPDATE tareas SET titulo=?,completada=? WHERE id=? AND alumno_id=?');$q->execute([$titulo,(int)$datos['completada'],$id,$usuario]);}
else{$q=bd()->prepare('DELETE FROM tareas WHERE id=? AND alumno_id=?');$q->execute([$id,$usuario]);}responder(['ok'=>true]);
