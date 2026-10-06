# Laboratorio de Ingeniería Web

Aplicación educativa para XAMPP: PHP 8.x puro, HTML, CSS, JavaScript y MySQL/MariaDB con PDO. No necesita Composer, Node ni servicios externos.

## Documentación del código para el alumno

Lee [Guía del código](documentacion/GUIA_DEL_CODIGO.md): un único documento con las capas de la aplicación, el orden de ejecución, los recorridos de datos, explicación por bloques y código completo de cada archivo.

## Descargar el proyecto como alumno

Sigue la [Guía para descargar el proyecto con Git](documentacion/GUIA_DESCARGA_GIT.md) para instalar Git y XAMPP, clonar el repositorio, importar la base de datos y ejecutar el laboratorio en tu computadora.

## Instalación

1. Instala XAMPP en `C:\xampp`.
2. Copia la carpeta completa `lab-web` a `C:\xampp\htdocs\lab-web`.
3. Abre el panel de XAMPP e inicia **Apache** y **MySQL**.
4. Abre `http://localhost/phpmyadmin`, elige **Importar** y selecciona `sql/lab_web.sql`. El script crea la base `lab_web`, sus tablas, 6 módulos, 30 preguntas y usuarios de prueba. No borra tablas existentes. Importarlo de nuevo no duplica módulos, preguntas ni usuarios.
5. Revisa las credenciales en `config/config.php`. Valores iniciales de XAMPP: host `127.0.0.1`, usuario `root`, contraseña vacía. Si cambias el puerto de MySQL, añádelo al DSN en `config/db.php`.
6. Abre **http://localhost/lab-web** y entra al módulo 6 para comprobar el entorno. Ese módulo se puede abrir aunque la base aún no esté importada.

## Usuarios de prueba

| Usuario o email | Contraseña | Rol |
|---|---|---|
| admin / admin@lab.local | admin123 | Docente |
| alumno / alumno@lab.local | alumno123 | Alumno |

Las contraseñas del SQL están almacenadas mediante `password_hash`; se verifican mediante `password_verify`. El registro siempre crea un alumno. Usa estos usuarios únicamente en el aula local y cambia sus contraseñas antes de cualquier publicación. No compartas públicamente un servidor con estas credenciales.

## Guía del docente

Se recomienda preparar XAMPP y la importación antes de la clase. Comienza por el módulo 6 y luego sigue 1 a 5.

| Módulo | Duración sugerida | Actividad |
|---|---|---|
| 1 · Evolución | 35 min | Explorar hitos y comparar HTML, PHP y fetch |
| 2 · Cliente-servidor | 40 min | Recorrer el flujo y enviar un formulario |
| 3 · HTTP/HTTPS | 60 min | Probar métodos, errores intencionales, cookies y sesiones |
| 4 · Tipos de apps | 60 min | Crear, editar, completar y eliminar tareas desde MPA y SPA |
| 5 · Plataformas | 35 min | Comparar entornos y guardar una checklist de despliegue |
| 6 · Entorno | 45 min | Diagnóstico y resolución de problemas de servicios |

Dedica los últimos 10 minutos de cada módulo al cuestionario y discusión. Cada evaluación registra un intento de cinco preguntas (2 puntos por acierto). El progreso es la proporción de módulos con evaluación; la portada muestra la media del último intento de cada módulo. El panel docente muestra todos los intentos. La checklist guarda acciones declaradas por el alumno, no realiza ni verifica despliegues.

## Seguridad como material didáctico

- Las consultas preparadas separan SQL y datos; las tareas están filtradas por propietario. Se añadió `alumno_id` a tareas para aislar alumnos.
- `e()` utiliza `htmlspecialchars` para texto/atributos HTML. JS usa `textContent` para JSON y textos de alumnos.
- Los formularios y peticiones que modifican datos requieren token CSRF de la sesión. Los GET de consulta no modifican datos.
- Se valida nuevamente en PHP aunque el navegador tenga `required`, límites y tipos.
- La sesión cambia de identificador al entrar y sus cookies son HttpOnly/SameSite; Secure se activa bajo HTTPS.
- El inspector usa una lista limitada de `$_SERVER` y cabeceras para no revelar credenciales o sesiones. Muestra `$_GET`, `$_POST` sin token y solo la cookie `lab_demo`.
- Es un laboratorio local de acceso sencillo. Un despliegue público necesita limitación de intentos, recuperación de acceso, configuración de secretos, copias de seguridad y revisión operativa.

## HTTPS en XAMPP

En `apache/conf/httpd.conf`, comprueba `LoadModule ssl_module` e `Include conf/extra/httpd-ssl.conf`. En el archivo incluido, verifica `Listen 443`, `SSLCertificateFile` y `SSLCertificateKeyFile` apuntando a los archivos que trae tu instalación. Reinicia Apache y entra a `https://localhost/lab-web`. Un certificado autofirmado local puede mostrar un aviso; en producción se necesita uno confiable. No cambies estos archivos sin una copia previa. No se modifican automáticamente desde el laboratorio.

## Diagnóstico y problemas comunes

- Puertos habituales: Apache 80/443 y MySQL 3306. `netstat -ano` permite identificar procesos en conflicto; revisa el PID en el Administrador de tareas. Si Apache usa otro puerto, incluye ese puerto en la URL.
- Si PDO falla, inicia MySQL, importa el SQL y revisa credenciales. Los detalles técnicos se escriben en el registro de errores, no en la página.
- Activa extensiones necesarias desde `php/php.ini` y reinicia Apache. Comprueba `pdo_mysql`, `mbstring`, `openssl`, `curl`.
- `mod_rewrite` es informativo: la aplicación funciona sin reglas de reescritura. En CLI/FastCGI puede no ser verificable.
- `display_errors` activo sirve para aprender en local; desactívalo en producción. El diagnóstico no modifica permisos ni configuración.
- La zona horaria se establece en `America/Bogota` para las fechas de PHP. Si el servidor MySQL usa otra zona, configura también su zona para las marcas de tiempo de la BD.

## Estructura y verificación manual

`includes/contenido.php` mantiene teoría y semillas de preguntas; la evaluación lee las preguntas de MySQL. `includes/modulo.php` comparte presentación y evaluación; cada carpeta conserva su entrada propia. `api/` devuelve JSON. `sql/generar.php` es una utilidad de desarrollo que regenera el SQL y sus hashes; no es necesaria para instalar.

Prueba registro y acceso; envía las cinco respuestas; revisa portada y panel admin. En módulo 4 crea una tarea en MPA, comprueba que aparece en SPA, edítala y elimínala. Usa otro alumno para confirmar aislamiento. Guarda y recarga la checklist. En HTTP prueba 200/404/500 (errores intencionales), crea una cookie, pulsa Leer, bórrala y vuelve a leer. Comprueba el modo oscuro y el diseño en móvil.
