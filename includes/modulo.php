<?php
require_once __DIR__.'/contenido.php';
require_once __DIR__.'/../config/db.php';
$c=$contenidos[$modulo];$titulo=$c[0];$mensaje='';
try {
 if($_SERVER['REQUEST_METHOD']==='POST') {
  exigir_login();csrf();
  if(isset($_POST['evaluar'])) {
   $q=bd()->prepare('SELECT * FROM preguntas WHERE modulo_id=? ORDER BY id');$q->execute([$modulo]);$preguntas=$q->fetchAll();
   if(count($preguntas)!==5) throw new RuntimeException('Importa las cinco preguntas del módulo.');
   $aciertos=0;foreach($preguntas as $p) { $r=$_POST['respuesta'][$p['id']] ?? null; if(!is_scalar($r) || !ctype_digit((string)$r) || !array_key_exists((int)$r,json_decode($p['opciones'],true))) throw new RuntimeException('Responde todas las preguntas.');$aciertos+=((int)$r===(int)$p['respuesta_correcta']); }
   $q=bd()->prepare('INSERT INTO resultados(alumno_id,modulo_id,puntaje) VALUES(?,?,?)');$q->execute([alumno()['id'],$modulo,$aciertos*2]);$mensaje='Nota registrada: '.($aciertos*2).'/10. Puedes repetir la evaluación.';
  }
 }
}catch(Throwable $error){$mensaje=$error->getMessage();}
include __DIR__.'/header.php';
?>
<p class="eyebrow">MÓDULO <?=e($modulo)?> / 06</p><h1><?=e($titulo)?></h1><p><a href="#teoria">Teoría</a> · <a href="#laboratorio">Laboratorio</a> · <a href="#evaluacion">Evaluación</a></p><?php if($mensaje): ?><p role="status" class="aviso"><?=e($mensaje)?></p><?php endif; ?>
<section id="teoria"><h2>01 · Comprende</h2><p><?=e($c[2])?></p>
<?php if($modulo===1): ?><div class="linea"><?php foreach($c[3] as $etapa): ?><button data-etapa="<?=e($etapa[1])?>"><?=e($etapa[0])?></button><?php endforeach; ?></div><p id="detalle-etapa" aria-live="polite">Selecciona una etapa para explorarla. La línea representa hitos conceptuales, no fechas exactas.</p>
<?php elseif($c[3]): ?><div class="tabla"><table><tr><th>Tipo / plataforma</th><th>Características</th></tr><?php foreach($c[3] as $fila): ?><tr><td><?=e($fila[0])?></td><td><?=e($fila[1])?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<div class="flujo" aria-label="Flujo cliente servidor"><?php foreach(['Navegador','DNS','Apache','PHP','MySQL','Respuesta'] as $paso): ?><span><?=e($paso)?></span><?php endforeach; ?></div><?php if($modulo===2): ?><button id="animar">Recorrer flujo</button><p>JS valida y envía desde el cliente. PHP valida, consulta y construye la respuesta en el servidor. El código PHP no se entrega al navegador.</p><?php endif; ?>
</section><section id="laboratorio"><h2>02 · Experimenta</h2>
<?php include __DIR__.'/laboratorios.php'; ?>
</section><section id="evaluacion"><h2>03 · Comprueba lo aprendido</h2><p>Cinco preguntas · 2 puntos por respuesta correcta · Se guarda cada intento. La portada utiliza tu último intento por módulo.</p>
<?php if(!alumno()): ?><p><a href="<?=BASE_URL?>/login.php">Inicia sesión para guardar tu evaluación.</a></p><?php else: try { $q=bd()->prepare('SELECT id,enunciado,opciones FROM preguntas WHERE modulo_id=? ORDER BY id');$q->execute([$modulo]);$lista=$q->fetchAll();if(count($lista)!==5) throw new RuntimeException('Faltan preguntas. Importa el SQL.'); ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><input type="hidden" name="evaluar" value="1"><?php foreach($lista as $p): ?><fieldset><legend><?=e($p['enunciado'])?></legend><?php foreach(json_decode($p['opciones'],true) as $i=>$opcion): ?><label><input type="radio" required name="respuesta[<?=e($p['id'])?>]" value="<?=e($i)?>"><?=e($opcion)?></label><?php endforeach; ?></fieldset><?php endforeach; ?><button>Enviar evaluación</button></form>
<?php }catch(Throwable $error){echo '<p class="aviso">'.e($error->getMessage()).'</p>';} endif; ?></section><?php include __DIR__.'/footer.php'; ?>
