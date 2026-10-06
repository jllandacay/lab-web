<?php
require __DIR__.'/comun.php';require __DIR__.'/../includes/contenido.php';metodo(['POST']);api_login();csrf();$items=$_POST['items']??[];
if(!is_array($items)||array_diff($items,$items_despliegue))responder(['error'=>'Elementos inválidos'],422);
$pdo=bd();$pdo->beginTransaction();try{$q=$pdo->prepare('INSERT INTO checklist_despliegue(alumno_id,item,completado) VALUES(?,?,?) ON DUPLICATE KEY UPDATE completado=VALUES(completado)');foreach($items_despliegue as $item)$q->execute([alumno()['id'],$item,(int)in_array($item,$items)]);$pdo->commit();}catch(Throwable $error){$pdo->rollBack();throw $error;}responder(['mensaje'=>'Checklist guardada: '.count($items).' de '.count($items_despliegue).' pasos.']);
