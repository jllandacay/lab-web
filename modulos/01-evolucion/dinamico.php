<?php require __DIR__.'/../../config/config.php'; ?>
<!doctype html><html lang="es"><meta charset="utf-8"><title>PHP dinámico</title><h1>Hola, clase</h1><p>Hora del servidor: <?=e(date('H:i:s'))?></p><p>PHP ejecuta date() antes de enviar HTML. Ver código fuente en el navegador muestra solo el resultado.</p></html>
