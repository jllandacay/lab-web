<?php
require __DIR__.'/../../config/db.php';exigir_login();$mensaje='';
try{if($_SERVER['REQUEST_METHOD']==='POST'){csrf();$accion=$_POST['accion']??'';$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$titulo=trim($_POST['titulo']??'');
// PDO separa la consulta de sus valores y evita la inyección SQL.
if(in_array($accion,['crear','actualizar'])&&($titulo===''||mb_strlen($titulo)>150))throw new RuntimeException('Título de 1 a 150 caracteres.');
if($accion==='crear'){$q=bd()->prepare('INSERT INTO tareas(alumno_id,titulo) VALUES(?,?)');$q->execute([alumno()['id'],$titulo]);}
elseif(in_array($accion,['actualizar','eliminar'])&&$id>0){if($accion==='actualizar'){$q=bd()->prepare('UPDATE tareas SET titulo=?,completada=? WHERE id=? AND alumno_id=?');$q->execute([$titulo,isset($_POST['completada'])?1:0,$id,alumno()['id']]);}else{$q=bd()->prepare('DELETE FROM tareas WHERE id=? AND alumno_id=?');$q->execute([$id,alumno()['id']]);}}
else throw new RuntimeException('Operación inválida.');header('Location: '.BASE_URL.'/modulos/04-tipos-apps/mpa.php');exit;}}
catch(Throwable $error){$mensaje=$error->getMessage();}
$titulo='CRUD MPA';include __DIR__.'/../../includes/header.php'; ?>
<h1>CRUD MPA</h1><p>PHP procesa el formulario y redirige a una nueva carga del documento.</p><a href="index.php">Volver al módulo y al CRUD SPA</a><?php if($mensaje): ?><p role="alert"><?=e($mensaje)?></p><?php endif; ?>
<section><form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><input type="hidden" name="accion" value="crear"><label>Nueva tarea <input name="titulo" required maxlength="150"></label><button>Crear</button></form></section>
<?php try{$q=bd()->prepare('SELECT * FROM tareas WHERE alumno_id=? ORDER BY id DESC');$q->execute([alumno()['id']]);foreach($q as $t): ?><section><form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><input type="hidden" name="id" value="<?=e($t['id'])?>"><label>Título <input name="titulo" required maxlength="150" value="<?=e($t['titulo'])?>"></label><label><input type="checkbox" name="completada" <?=$t['completada']?'checked':''?>>Completada</label><button name="accion" value="actualizar">Guardar</button><button name="accion" value="eliminar" formnovalidate>Eliminar</button></form></section><?php endforeach;}catch(Throwable $error){echo '<p>'.e($error->getMessage()).'</p>';}include __DIR__.'/../../includes/footer.php'; ?>
