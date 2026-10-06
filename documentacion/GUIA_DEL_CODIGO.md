# Guía del código: Laboratorio de Ingeniería Web

Documento para estudiantes principiantes. Su objetivo es que puedas explicar qué hace cada archivo, dónde se ejecuta y cómo se comunica con los demás. Los ejemplos corresponden al código de este proyecto.

## Índice de lectura

1. El mapa del sistema y sus capas.
2. El orden de ejecución de una página y de una API.
3. Herramientas del lenguaje para leer el código.
4. Recorridos completos: acceso, evaluación, tareas y checklist.
5. La base de datos y sus relaciones.
6. Seguridad, errores y depuración.
7. Ruta sugerida de aprendizaje y ejercicios.
8. Explicación archivo por archivo, con el código completo.

## 1. El mapa del sistema: cómo se llaman las capas

Una **capa** es un grupo de responsabilidades. No es un programa que se inicia por separado. En este proyecto las capas se reconocen por lo que hace el código; varios archivos mezclan responsabilidades para mantener el ejemplo pequeño.

| Capa | Responsabilidad | Código del proyecto | Lugar de ejecución |
|---|---|---|---|
| Presentación | Mostrar contenido, formularios y mensajes | HTML generado por las páginas, `includes/header.php`, `nav.php`, `footer.php`, CSS | El servidor genera parte del HTML; el navegador lo dibuja |
| Interacción del cliente | Reaccionar a clics, enviar peticiones y actualizar la pantalla | `assets/js/app.js`, `fetch.html` | Navegador |
| Entrada y control | Recibir una petición y decidir qué operación corresponde | `login.php`, `includes/modulo.php`, `mpa.php`, archivos de `api/` | PHP en el servidor |
| Lógica de aplicación | Validar, calcular notas, comprobar permisos y organizar operaciones | Funciones y bloques PHP de páginas y APIs | Servidor |
| Acceso a datos | Conectar y ejecutar consultas | `config/db.php` y llamadas a `prepare`, `execute`, `query` | PHP, conectado a MySQL/MariaDB |
| Persistencia | Conservar alumnos, tareas, preguntas y resultados | Tablas creadas por `sql/lab_web.sql` | MySQL/MariaDB |
| Configuración y utilidades compartidas | Definir parámetros, sesión, escape y CSRF | `config/config.php`, `api/comun.php` | Servidor |

La arquitectura puede entenderse como **cliente → servidor → datos**. Apache es el servidor web que recibe HTTP. PHP es el lenguaje que ejecuta la aplicación. MySQL/MariaDB es el gestor de base de datos. XAMPP reúne estos programas para trabajar localmente.

El proyecto no implementa un patrón MVC formal: no hay clases de modelos ni controladores separados. Las páginas y endpoints incluyen directamente validación, consultas y presentación. Identificar capas ayuda a comprender sus responsabilidades aunque compartan un archivo.

### Mapa de carpetas

```text
lab-web/
├── index.php                 Portada y progreso
├── login.php / logout.php    Entrada y salida de la sesión
├── docente.php               Historial de evaluaciones
├── config/                   Parámetros, sesión y conexión PDO
├── includes/                 Piezas reutilizadas y contenido de módulos
├── modulos/                  Entradas de los seis módulos y ejemplos
├── api/                      Operaciones HTTP que responden JSON
├── assets/
│   ├── css/                  Presentación visual
│   ├── js/                   Interacción del navegador
│   └── img/                  Carpeta disponible para imágenes
├── sql/                      Esquema, datos iniciales y generador
├── documentacion/            Este documento
└── README.md                 Instalación y guía del docente
```

## 2. El orden: qué ocurre primero

Los archivos no se ejecutan todos en el orden de las carpetas. **Cada petición HTTP tiene un punto de entrada**. PHP empieza por el archivo solicitado y ejecuta sus instrucciones de arriba hacia abajo. Cuando encuentra un `require` o `include`, ejecuta el archivo incluido en ese punto y después continúa.

### 2.1 Al abrir la portada

1. El alumno escribe `http://localhost/lab-web/index.php`.
2. El equipo resuelve `localhost` hacia su propia interfaz local. Habitualmente no consulta un DNS externo.
3. Apache busca `C:\xampp\htdocs\lab-web\index.php`.
4. PHP ejecuta `index.php`, que carga `config/db.php`.
5. `db.php` carga `config/config.php`: constantes, zona horaria y sesión. También quedan disponibles las funciones compartidas.
6. `index.php` carga `includes/contenido.php` para conocer los seis módulos.
7. Si hay alumno autenticado, ejecuta una consulta mediante `bd()` para recuperar sus últimos resultados. La conexión se crea al llamar `bd()`, no al incluir el archivo.
8. Se incluye `header.php`; su `require_once` evita volver a cargar la configuración. Se imprime el comienzo del HTML y se incluye `nav.php`.
9. La portada imprime progreso, media y tarjetas. `footer.php` cierra el documento.
10. Apache envía el HTML resultante. El navegador no recibe las instrucciones PHP originales.
11. El navegador solicita CSS y JavaScript por sus URLs. Aplica el CSS y ejecuta el script marcado `defer` después de analizar el HTML.
12. El JavaScript conecta los botones y formularios que existen en esta página. El operador `?.` evita errores si un elemento no está presente.

```text
Petición del navegador
        ↓
Apache → index.php
             ├─ config/db.php → config/config.php
             ├─ includes/contenido.php
             ├─ consulta de resultados → MySQL
             ├─ includes/header.php → includes/nav.php
             ├─ HTML de la portada
             └─ includes/footer.php
        ↓
Respuesta HTML → navegador → CSS + JavaScript
```

### 2.2 Al abrir un módulo

Por ejemplo, `modulos/02-cliente-servidor/index.php` establece `$modulo=2` y carga `includes/modulo.php`. Ese archivo consulta el contenido correspondiente, procesa una evaluación si la petición es POST, imprime la teoría, incluye el laboratorio elegido e imprime las cinco preguntas almacenadas en MySQL.

El archivo de entrada tiene dos instrucciones porque la estructura común se reutiliza. El módulo 1 cambia `$modulo` a 1, el módulo 3 a 3, y así sucesivamente. La variable está disponible en el archivo incluido porque PHP comparte el ámbito del lugar donde se hace la inclusión.

### 2.3 Al enviar una operación con fetch

```text
Alumno pulsa un botón
        ↓
app.js valida / recoge datos
        ↓ fetch: nueva petición HTTP
api/tareas.php → api/comun.php → config/db.php → config/config.php
        ↓ comprobación de método, sesión, CSRF y datos
Consulta preparada → MySQL
        ↓
responder() → JSON + código HTTP
        ↓
JavaScript recibe JSON → actualiza el DOM
```

La petición de la API tiene una ejecución PHP nueva. Se recupera la misma sesión mediante su cookie, pero las variables locales PHP de la petición anterior no permanecen. Los datos que deben continuar entre peticiones se guardan en sesión o en la base de datos.

**Definir una función no ejecuta su cuerpo.** `function bd()` declara una herramienta; la conexión aparece cuando otra instrucción llama `bd()`. Igualmente, un `addEventListener` registra lo que ocurrirá cuando llegue un evento: no equivale a ejecutar inmediatamente toda la acción.

## 3. Cómo leer la sintaxis

### 3.1 PHP

| Expresión | Significado en este proyecto |
|---|---|
| `<?php ... ?>` | Delimita código PHP dentro del documento |
| `<?=e($titulo)?>` | Imprime el valor escapado; equivale a `echo e($titulo)` |
| `$variable` | Variable PHP; su nombre empieza por `$` |
| `const DB_NAME = 'lab_web'` | Define un valor de configuración |
| `['id'=>1, 'nombre'=>'Ana']` | Arreglo asociativo: claves y valores |
| `$usuario['id']` | Lee el valor de una clave del arreglo |
| `.` | Concatena cadenas de texto |
| `__DIR__` | Directorio del archivo PHP donde aparece |
| `require` | Carga un archivo obligatorio; si falla, interrumpe la ejecución |
| `require_once` | Lo carga como máximo una vez en la petición |
| `include` | Inserta y ejecuta otro archivo; se usa para las piezas de presentación |
| `??` | Usa el valor de la izquierda si existe y no es null; de lo contrario usa el de la derecha |
| `??=` | Asigna el valor de la derecha únicamente si falta o es null |
| `===` | Compara valor y tipo; `1` y `'1'` no son idénticos |
| `foreach` | Recorre elementos de un arreglo o resultados consultados |
| `if (...) : ... endif;` | Forma de condicional cómoda cuando hay HTML entre instrucciones |
| `try / catch` | Intenta una operación y maneja una excepción si falla |
| `throw new RuntimeException(...)` | Interrumpe el flujo normal comunicando un error |
| `->` | Accede a un método o propiedad de un objeto, como `$q->execute()` |
| `: string`, `: array`, `: void` | Declaran el tipo de retorno de una función |
| `?array` | La función puede devolver un arreglo o null |
| `static $pdo` | Variable local que se conserva entre llamadas a la función dentro de la misma petición |

En `api/comun.php`, `responder()` aparece con `: never` y termina con `exit`. El tipo especial `never` se incorporó en PHP 8.1. En PHP 8.0 puede analizarse como nombre de tipo, pero no ofrece esa comprobación especial; la aplicación comprobada en PHP 8.0.30 funciona porque la función siempre termina el proceso con `exit`. Para enseñar tipos en PHP 8.0 conviene explicar `void` y la instrucción `exit` por separado.

### 3.2 Superglobales de PHP

| Variable | Datos que contiene | Ejemplo |
|---|---|---|
| `$_GET` | Parámetros de la URL | `?estado=404` |
| `$_POST` | Datos de formularios POST compatibles | Nombre, contraseña, respuestas |
| `$_SERVER` | Información de la petición y del servidor | Método HTTP, URI, HTTPS |
| `$_COOKIE` | Cookies recibidas en esta petición | `lab_demo` |
| `$_SESSION` | Datos recuperados de la sesión del servidor | Alumno autenticado, token, valor de demostración |

PHP no coloca automáticamente un cuerpo JSON en `$_POST`. Por eso `cuerpo()` lee `php://input` y llama `json_decode`. Para PUT y DELETE, el CRUD SPA envía JSON y utiliza esa función.

### 3.3 JavaScript y el DOM

El **DOM** es la representación del documento como nodos que JavaScript puede consultar o modificar. `document.querySelector('#tareas')` busca el elemento con ese identificador. `createElement` crea un nodo; `append` lo inserta; `replaceChildren` sustituye sus hijos.

`addEventListener('submit', ...)` conecta una función al envío de un formulario. `evento.preventDefault()` cancela el envío tradicional para que `fetch` lo reemplace. `new FormData(formulario)` recoge los controles con atributo `name`.

`async` permite usar `await`. Cuando se espera la red, el navegador puede seguir atendiendo otros eventos. `fetch` devuelve una respuesta HTTP, y `respuesta.json()` interpreta su cuerpo JSON. Una respuesta 404 no rechaza por sí sola la promesa de `fetch`: el programa debe revisar `respuesta.ok` o `respuesta.status`.

El operador `...` copia propiedades. En `peticion()`, se combinan opciones comunes y específicas, y se adjunta `X-CSRF-Token`. `JSON.stringify` transforma un objeto JavaScript en texto JSON para enviarlo o mostrarlo.

### 3.4 HTML y CSS

HTML define significado y estructura: `nav` para navegación, `main` para contenido principal, `section` para una sección, `form` para entrada y `table` para datos comparables. `label` identifica un control; `fieldset` agrupa respuestas; `legend` presenta la pregunta.

El atributo `name` decide la clave enviada al servidor. El atributo `id` permite localizar un elemento y debe identificarlo de forma única en el documento. `required`, `maxlength`, `minlength` y `type="email"` permiten validación básica en el navegador.

CSS aplica reglas del tipo `selector { propiedad: valor; }`. Las clases llevan punto, como `.tarjeta`; los identificadores llevan `#`. Las variables `--fondo` y `--texto` se usan con `var(...)`. Cambiar sus valores en `html.oscuro` modifica todo el tema. Grid organiza tarjetas; Flexbox organiza filas; `@media` adapta el diseño cuando la ventana es estrecha.

## 4. Recorridos completos que debes poder explicar

### 4.1 Registro e inicio de sesión

**Registro:** el formulario envía POST a `login.php`. PHP comprueba CSRF, nombre, email y longitud de contraseña. Busca si el email existe. Si no existe, usa `password_hash` e inserta el alumno. La tabla asigna el rol `alumno` por defecto: el usuario no puede elegir `admin` desde el registro.

**Acceso:** el otro formulario también envía POST a `login.php`, pero sin la marca `registro`. Se normalizan los alias de prueba `admin` y `alumno` a sus emails. Se consulta el usuario, se comprueba el hash con `password_verify`, se regenera el identificador de sesión y se guarda una copia del usuario sin contraseña en `$_SESSION['alumno']`. Se renueva el token CSRF y se redirige a la portada.

La cookie de sesión permite que las peticiones siguientes recuperen esa sesión. No contiene todas las notas ni la contraseña. `alumno()` devuelve los datos de usuario almacenados en el servidor. `exigir_login()` redirige las páginas HTML al acceso; `api_login()` devuelve JSON y estado 401 en las APIs.

### 4.2 Guardar una evaluación

1. El navegador recibe cinco preguntas consultadas en MySQL. No se envían las respuestas correctas en los campos de la evaluación.
2. El alumno selecciona opciones. El nombre de cada radio tiene la forma `respuesta[ID]`.
3. El formulario envía el token, `evaluar=1` y el arreglo de respuestas al mismo módulo.
4. `includes/modulo.php` exige sesión y CSRF.
5. Consulta las preguntas del módulo con sus soluciones guardadas en MySQL.
6. Comprueba que sean cinco y que cada respuesta sea un índice válido de las opciones.
7. Compara las respuestas con `respuesta_correcta`, suma aciertos y multiplica por dos.
8. Inserta un intento en `resultados` y muestra la nota.
9. La portada consulta posteriormente el intento con mayor ID de cada módulo para ese alumno.

Una nota de 4 significa dos aciertos. La media usa los módulos evaluados; los pendientes no cuentan como cero. El progreso representa módulos con evaluación, no minutos de lectura ni porcentaje de aciertos. El formulario de evaluación vuelve a renderizarse después del POST; recargar y reenviar ese POST puede registrar otro intento.

### 4.3 CRUD MPA y SPA

CRUD significa **crear, leer, actualizar y eliminar**.

| Operación | MPA: `mpa.php` | SPA: `app.js` + `api/tareas.php` |
|---|---|---|
| Leer | PHP consulta y genera HTML | GET devuelve JSON; JS crea nodos |
| Crear | POST con `accion=crear` | POST con el título |
| Actualizar | POST con `accion=actualizar`, ID y título | PUT con JSON: ID, título y completada |
| Eliminar | POST con `accion=eliminar` | DELETE con JSON: ID |
| Después del cambio | Redirección y nueva carga del documento | Nueva consulta y actualización de la lista |

Ambas usan la tabla `tareas`. Cada consulta incluye `alumno_id` para que un alumno solo vea y modifique sus registros. Compartir tabla no significa que todos compartan tareas. Si cambias datos en una pestaña, la otra vista los verá al volver a consultar; no existe sincronización en tiempo real.

La redirección de MPA aplica el patrón **POST → redirect → GET**. Reduce los reenvíos accidentales al recargar. La SPA usa funciones asíncronas y vuelve a cargar la lista después de cada operación.

### 4.4 Checklist

`laboratorios.php` consulta los pasos existentes del alumno y marca sus casillas. Al enviar, `app.js` recoge `FormData` y llama a `api/checklist.php`. Las casillas sin marcar no se incluyen en el formulario. PHP valida los valores contra `$items_despliegue` y recorre **todos** los pasos permitidos para guardar también los desmarcados.

Se usa una transacción: o se guardan todos los pasos, o se revierte el conjunto. La clave única `(alumno_id,item)` permite actualizar un paso ya existente con `ON DUPLICATE KEY UPDATE`. Esta lista registra lo que declara el alumno; no sube archivos ni configura servicios.

## 5. Base de datos: estructura y relaciones

Una tabla agrupa registros; una fila es un registro; una columna almacena una característica. La clave primaria identifica la fila. Una clave foránea conecta una tabla con otra y evita referencias inexistentes.

| Tabla | Datos y propósito | Relaciones |
|---|---|---|
| `alumnos` | ID, nombre, email único, hash de contraseña, rol y fecha | Un alumno puede tener muchos resultados, tareas y pasos |
| `modulos` | ID fijo, título, slug y orden | Un módulo tiene preguntas y resultados |
| `preguntas` | Enunciado, opciones JSON e índice correcto | `modulo_id` apunta al módulo |
| `resultados` | Alumno, módulo, puntaje y fecha de cada intento | Apunta a alumno y módulo |
| `tareas` | Alumno propietario, título, estado y fecha | `alumno_id` apunta al alumno |
| `checklist_despliegue` | Alumno, descripción del paso y estado | Una combinación alumno/paso es única |

```text
alumnos 1 ─── muchos resultados muchos ─── 1 modulos
   │                                          │
   ├── muchos tareas                          └── muchos preguntas
   └── muchos checklist_despliegue
```

`AUTO_INCREMENT` genera un identificador. `NOT NULL` exige un valor. `DEFAULT` establece un valor inicial. `UNIQUE` evita duplicados. `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` guarda una fecha asignada por MySQL. `BOOLEAN` representa aquí 0 o 1. `JSON` contiene una lista de opciones; la opción correcta se guarda como índice empezando en cero: 0 significa la primera.

### 5.1 Cómo leer una consulta preparada

```php
$q = bd()->prepare('SELECT id,titulo FROM tareas WHERE alumno_id=?');
$q->execute([alumno()['id']]);
$filas = $q->fetchAll();
```

`bd()` devuelve el objeto PDO. `prepare` define la consulta con un marcador `?`. `execute` proporciona el valor del alumno en el mismo orden de los marcadores. `fetchAll` obtiene todas las filas como arreglos asociativos. Los valores se envían separados de la estructura SQL; no se concatenan con la consulta.

`query` se utiliza en consultas fijas sin entrada del usuario. `fetch()` obtiene una fila. `lastInsertId()` recupera el ID generado tras una inserción.

La consulta de progreso agrupa resultados por módulo y selecciona `MAX(id)`. Después usa un `JOIN` para recuperar el puntaje de esos intentos concretos. No toma `MAX(puntaje)`: mostrar la nota más alta sería una regla distinta a mostrar la última.

## 6. Seguridad y errores: dónde intervienen

### 6.1 Cuatro controles distintos

1. **Autenticación:** identifica al usuario mediante contraseña y sesión.
2. **Autorización:** decide si ese usuario puede abrir el panel docente o modificar una tarea concreta.
3. **CSRF:** comprueba que una operación de escritura lleva el token asociado a la sesión.
4. **Validación y escape:** valida datos de entrada y codifica la salida para evitar interpretaciones peligrosas.

Ninguno sustituye a los demás. Estar autenticado no concede permiso sobre todas las tareas. Un token correcto no hace válido un título vacío. Un dato válido también debe escaparse al imprimirlo en HTML.

### 6.2 XSS, SQL y contraseñas

`e()` llama a `htmlspecialchars`. Si un alumno escribe `<script>`, se muestra como texto al imprimirlo mediante `e()`, en vez de convertirse en una etiqueta. Este escape corresponde al contexto HTML de texto y atributos entre comillas usado aquí. No es un mecanismo general para construir SQL o JavaScript. En el cliente se usa `textContent` para los datos; el DOM no los interpreta como HTML.

Las consultas preparadas evitan que un valor se mezcle con instrucciones SQL. `password_hash` produce un hash de contraseña, no un cifrado que se descifre luego. `password_verify` compara una contraseña propuesta con ese hash.

La validación del navegador mejora la experiencia, pero se puede omitir desde otra herramienta HTTP. Por eso PHP vuelve a comprobar límites, tipos, estados y opciones.

### 6.3 Cookies, sesión y CSRF

`session_start()` recupera o crea la sesión. `HttpOnly` impide leer su cookie desde JavaScript. `SameSite=Lax` limita su envío en ciertos contextos entre sitios. `Secure` exige HTTPS cuando está activado. El token CSRF se crea con bytes aleatorios, se imprime en un campo oculto o una etiqueta `meta` y se compara con `hash_equals`.

En el laboratorio de cookies, `setcookie()` añade una cabecera a la respuesta. `$_COOKIE` muestra lo que llegó **en la petición actual**, por eso la cookie recién creada se observa al pulsar Leer en una petición posterior. La variable de sesión puede actualizarse inmediatamente en el servidor.

### 6.4 Errores y estados

| Estado | Uso en el proyecto |
|---|---|
| 200 | Consulta u operación correcta |
| 201 | Tarea creada |
| 302 | Redirección después de acceso o cambio MPA |
| 400 | Error de aplicación gestionado sin otro estado específico |
| 401 | Falta inicio de sesión en API |
| 403 | CSRF inválido o acceso docente no autorizado |
| 404 | Tarea inexistente para ese propietario; simulación del inspector |
| 405 | Método HTTP no permitido |
| 422 | Datos que no cumplen la validación |
| 500 | Error interno inesperado; simulación del inspector |

Las páginas suelen mostrar un mensaje dentro del HTML. Las APIs devuelven `{"error":"..."}` y un estado. El inspector permite provocar estados de error deliberadamente: su resultado puede ser correcto para el ejercicio aunque la respuesta sea 404 o 500.

`error_log` registra detalles técnicos en el servidor. El alumno recibe un mensaje más sencillo para fallos de conexión y errores inesperados de API. `display_errors` se comprueba para el trabajo local; un servidor público debe configurarse para no revelar errores internos.

## 7. Cómo estudiar y depurar este proyecto

### Orden recomendado de lectura

1. `estatico.html`: entiende lo que recibe y dibuja el navegador.
2. `dinamico.php`: distingue la plantilla PHP del HTML resultante.
3. `config/config.php` y `config/db.php`: identifica utilidades y conexión.
4. `header.php`, `nav.php`, `footer.php` e `index.php`: sigue las inclusiones.
5. Las entradas de módulos y `includes/modulo.php`: comprende la reutilización.
6. `login.php`, la tabla `alumnos` y `logout.php`: recorre la sesión.
7. `api/comun.php`, `saludo.php`, `procesar.php` y `fetch.html`: conecta cliente y servidor.
8. `mpa.php`, `api/tareas.php` y las funciones de tareas de `app.js`: compara dos interfaces para los mismos datos.
9. Evaluaciones, checklist y panel docente: sigue las relaciones SQL.
10. `estilos.css`, diagnóstico e inspector: relaciona presentación y entorno.

### Herramientas que te dan evidencia

- **Ver código fuente de la página:** muestra el HTML enviado; no recupera el PHP original.
- **Herramientas del navegador → Red:** permite observar URL, método, cuerpo enviado, estado y respuesta de una API.
- **Consola:** muestra errores JavaScript. Un error aquí puede impedir que se conecten eventos.
- **phpMyAdmin:** permite comprobar si el cambio llegó a las tablas. No publiques contraseñas ni hashes en capturas.
- **Registros de Apache/PHP:** permiten investigar fallos del servidor que no aparecen completos en pantalla.
- **Módulo 6:** ayuda a comprobar servicios y extensiones; no cambia la configuración por sí mismo.

### Ejercicios de comprensión

1. Dibuja el recorrido desde el botón Crear de SPA hasta la nueva fila de MySQL y su vuelta a pantalla. Escribe el archivo de cada paso.
2. Explica por qué se usa `alumno_id` además del ID de tarea. Comprueba con dos cuentas que sus listas son diferentes.
3. Introduce un título con signos `<` y `>` y observa su representación en MPA y SPA. Localiza `e()` y `textContent`.
4. Completa una evaluación con dos aciertos. Predice la nota antes de enviarla y busca el registro en `resultados`.
5. Crea una cookie y distingue `cookie_recibida` de `sesion_actual`. Explica por qué debes hacer otra petición para leer la cookie.
6. Identifica una validación del navegador y su equivalente en PHP. Explica qué ocurriría si solo existiera la primera.
7. En una copia del proyecto, cambia un color de `--acento` y observa qué componentes dependen de él.

### Límites que ayudan a interpretar el ejemplo

La línea temporal organiza hitos sin fechas exactas. El flujo animado es una representación didáctica; no mide el tiempo real de DNS, Apache o MySQL. El formulario del módulo 2 no consulta una tabla porque su respuesta no necesita persistencia. La sección SPA del módulo 4 utiliza navegación y APIs para el CRUD; el sitio completo sigue teniendo múltiples páginas PHP. El diagnóstico de escritura consulta permisos con `is_writable`, no prueba crear archivos. Estas distinciones permiten separar una visualización del comportamiento real.

## 8. Explicación archivo por archivo y código completo

En cada apartado se indica qué recibe el archivo, cómo trabaja y qué produce. El código se incluye completo para estudiar sin cambiar de documento. Su orden es pedagógico; el orden real de ejecución depende de la petición y las inclusiones explicadas antes.

Los bloques de código son una instantánea de la versión documentada. Si modificas el proyecto, comprueba el archivo ejecutable correspondiente antes de comparar resultados. Los hashes de contraseña del SQL se sustituyen por una marca en este documento para que la atención se centre en la estructura; el archivo SQL instalable conserva sus valores reales.

### 8.1 · `config/config.php`

**Responsabilidad.** Configuración y utilidades. Se carga antes de emitir HTML o JSON porque configura la cookie y abre la sesión.

**Bloque 1.** Primero activa strict_types, declara BASE_URL y los parámetros de MySQL. BASE_URL es una ruta web; __DIR__ es una ruta del disco. En app.js la ruta también está escrita como /lab-web: si renombras la carpeta, actualiza ambos sitios.

**Bloque 2.** Después fija la zona horaria, configura la cookie de sesión y llama a session_start. Las constantes no abren la base de datos.

**Bloque 3.** e() convierte a texto y escapa caracteres HTML. token() conserva el token en sesión mediante ??=. csrf() acepta el token de un formulario o de la cabecera X-CSRF-Token, compara con hash_equals y lanza una excepción con estado 403 si no coincide.

**Bloque 4.** alumno() devuelve el usuario de la sesión o null. exigir_login() envía Location y exit si falta usuario. exit evita que el resto de la página protegida se ejecute.

**Código para estudiar:**

```php
<?php
declare(strict_types=1);
const BASE_URL = '/lab-web';
const DB_HOST = '127.0.0.1';
const DB_NAME = 'lab_web';
const DB_USER = 'root';
const DB_PASS = '';
date_default_timezone_set('America/Bogota');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
function e($valor): string { return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8'); }
function token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf(): void {
    // El token evita que otro sitio envíe operaciones en nombre del alumno.
    if (!hash_equals(token(), $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) { http_response_code(403); throw new RuntimeException('Token CSRF inválido. Recarga la página.'); }
}
function alumno(): ?array { return $_SESSION['alumno'] ?? null; }
function exigir_login(): void { if (!alumno()) { header('Location: '.BASE_URL.'/login.php'); exit; } }
```

### 8.2 · `config/db.php`

**Responsabilidad.** Acceso a datos. Su entrada son las constantes de configuración; su resultado es un objeto PDO o una excepción.

**Bloque 1.** require_once garantiza que config.php se ejecute una vez por petición. Dentro de bd(), static $pdo permite reutilizar la conexión si la función se llama varias veces en esa petición.

**Bloque 2.** El DSN indica motor, host, base y utf8mb4. ERRMODE_EXCEPTION hace que los fallos SQL lancen excepciones; FETCH_ASSOC devuelve claves por nombre de columna; EMULATE_PREPARES=false pide preparaciones nativas.

**Bloque 3.** El catch registra el detalle técnico y transforma el fallo en un mensaje didáctico. La función devuelve la conexión después de crearla o recuperar la ya creada.

**Código para estudiar:**

```php
<?php
require_once __DIR__.'/config.php';
function bd(): PDO {
    static $pdo;
    if (!$pdo) {
        try { $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); }
        catch (PDOException $error) { error_log($error->getMessage()); throw new RuntimeException('No se pudo conectar con lab_web. Inicia MySQL e importa sql/lab_web.sql.'); }
    }
    return $pdo;
}
```

### 8.3 · `includes/contenido.php`

**Responsabilidad.** Contenido compartido. Declara tres conjuntos de datos sin producir HTML ni realizar consultas.

**Bloque 1.** $contenidos usa el ID del módulo como clave. Cada valor contiene título, slug, explicación y una lista de pares para hitos o tablas. Los índices 0, 1, 2 y 3 tienen significados diferentes: observa dónde se accede a ellos.

**Bloque 2.** $preguntas_semilla organiza las preguntas iniciales por módulo. Cada pregunta contiene enunciado, opciones e índice correcto. generar.php utiliza estas semillas para crear SQL; la evaluación en funcionamiento consulta preguntas desde MySQL.

**Bloque 3.** $items_despliegue es la lista permitida de pasos. Se usa tanto para dibujar las casillas como para validar lo recibido en la API.

**Código para estudiar:**

```php
<?php
$contenidos = [
1=>['Evolución del desarrollo web','01-evolucion','La web pasó de documentos enlazados a aplicaciones que combinan servicios, interfaces y datos. Cada etapa añade posibilidades; HTML estático sigue siendo útil.',[
['Web 1.0','Documentos HTML estáticos enlazados.'],['CGI','Programas ejecutados por el servidor para generar respuestas.'],['PHP / ASP','Generación dinámica con lógica y bases de datos.'],['AJAX','Peticiones asíncronas sin recargar todo el documento.'],['Web 2.0','Participación, colaboración y contenido de usuarios.'],['SPA','La interfaz cambia en el navegador usando APIs.'],['Nube','Infraestructura y servicios bajo demanda.'],['PWA','Aplicaciones con capacidades instalables y trabajo sin conexión.'],['Jamstack','Contenido preconstruido, JavaScript y APIs.'],['IA en la web','Funciones que generan o clasifican contenido; requieren validación.']]],
2=>['Arquitectura cliente-servidor','02-cliente-servidor','El navegador presenta la interfaz. DNS resuelve nombres; Apache recibe HTTP; PHP ejecuta la lógica; MySQL conserva datos. La respuesta vuelve al navegador. En localhost, el nombre suele resolverse localmente sin una consulta DNS externa.',[]],
3=>['HTTP / HTTPS','03-http-https','HTTP intercambia peticiones y respuestas. GET consulta; POST crea o procesa; PUT reemplaza; DELETE elimina. 200 indica éxito, 400 datos inválidos, 404 recurso ausente y 500 error del servidor. Las cabeceras describen contenido y contexto. Las cookies viajan con las peticiones; la sesión conserva datos en el servidor. HTTPS usa TLS para cifrar y autenticar la conexión; SSL es su antecesor.',[]],
4=>['Tipos de aplicaciones web','04-tipos-apps','Una MPA solicita documentos completos en cada navegación. Una SPA actualiza la interfaz con JavaScript y una API. Ambas pueden usar la misma base de datos. PWA describe capacidades adicionales y no implica una arquitectura única.',[
['Estática','HTML preconstruido: portafolio o documentación.'],['Dinámica','El servidor genera contenido según datos o usuario.'],['SPA','Actualizaciones de interfaz mediante API.'],['MPA','Navegaciones y formularios cargan documentos.'],['PWA','Manifiesto y capacidades progresivas, como caché sin conexión.'],['E-commerce','Catálogo, pedidos y pagos.'],['CMS','Gestión editorial de contenidos.'],['API REST','Recursos accesibles mediante HTTP, habitualmente JSON.']]],
5=>['Plataformas y despliegue','05-plataformas','El entorno se elige según lenguaje, almacenamiento, control operativo y presupuesto. Una aplicación PHP con MySQL necesita un servidor compatible y una base de datos; un alojamiento de archivos estáticos por sí solo no ejecuta PHP.',[
['XAMPP / Laragon','Desarrollo local con servicios integrados.'],['Docker','Contenedores reproducibles; requiere configurar imágenes y volúmenes.'],['Hosting compartido','Administración sencilla; recursos y configuración limitados.'],['VPS','Control del servidor; exige mantenimiento y seguridad.'],['Render / Railway','PaaS: despliegue administrado, compatibilidad según servicio e imagen.'],['Netlify / Vercel / GitHub Pages','Publicación estática o funciones según proveedor; este PHP no se sube tal cual.']]],
6=>['Configuración del entorno','06-entorno','XAMPP integra Apache, PHP y MariaDB. El panel inicia los servicios; phpMyAdmin administra bases de datos. Verificar el entorno permite distinguir errores de código de errores de configuración.',[]]
];
$preguntas_semilla = [
1=>[['¿Qué caracteriza a Web 1.0?',['Documentos estáticos','Sesiones de PHP','IA generativa'],0],['¿Qué permite AJAX?',['Peticiones sin recargar todo','Eliminar HTTP','Ejecutar PHP en el navegador'],0],['¿Dónde se ejecuta PHP?',['Navegador','Servidor','DNS'],1],['¿Qué caracteriza a una SPA?',['Actualiza la interfaz con JavaScript','Solo admite HTML estático','No usa HTTP'],0],['¿Qué aporta una PWA?',['Capacidades progresivas como instalación','Reemplaza MySQL','Necesita CGI'],0]],
2=>[['¿Qué resuelve DNS?',['Nombres a direcciones','Consultas SQL','Contraseñas'],0],['¿Qué hace Apache?',['Recibe peticiones HTTP','Dibuja botones','Ejecuta JavaScript del cliente'],0],['¿Dónde corre JavaScript de la interfaz?',['MySQL','Navegador','DNS'],1],['¿Qué conserva MySQL?',['Datos persistentes','Solo estilos','Certificados TLS'],0],['¿Qué devuelve una API JSON?',['Datos estructurados','Código PHP para ejecutar en el cliente','Un servidor nuevo'],0]],
3=>[['¿Qué método consulta recursos?',['GET','DELETE','PUT'],0],['¿Qué significa 404?',['Éxito','Recurso no encontrado','Redirección'],1],['¿Qué añade HTTPS?',['Cifrado TLS','Una base de datos','Elimina cookies'],0],['¿Dónde se guardan los datos de sesión de PHP?',['Servidor','Solo en CSS','DNS'],0],['¿Qué método se usa para eliminar un recurso?',['GET','DELETE','PUT'],1]],
4=>[['¿Qué hace una MPA al enviar un formulario tradicional?',['Recarga un documento','No contacta el servidor','Ejecuta SQL en el cliente'],0],['¿Qué usa este CRUD SPA?',['fetch y JSON','Composer','FTP'],0],['¿Qué significa CRUD?',['Crear, leer, actualizar y eliminar','Cifrar rutas','Solo consultar'],0],['¿Qué es un CMS?',['Gestor de contenidos','Certificado','Método HTTP'],0],['¿Pueden SPA y MPA compartir tabla?',['Sí','Nunca','Solo sin PHP'],0]],
5=>[['¿Qué ofrece un VPS?',['Control del servidor','Solo archivos HTML','Un navegador'],0],['¿Qué necesita esta aplicación?',['PHP y MySQL','Solo alojamiento estático','Solo DNS'],0],['¿Para qué sirve un dominio?',['Identificar un sitio con un nombre','Guardar tareas','Cifrar contraseñas'],0],['¿Qué debe habilitarse al publicar?',['HTTPS','display_errors público','Contraseñas vacías'],0],['¿Qué permite Docker?',['Entornos en contenedores','Eliminar validaciones','Reemplazar HTTP'],0]],
6=>[['¿Qué extensión permite PDO con MySQL?',['pdo_mysql','gd','zip'],0],['¿Cuál es el puerto HTTP habitual?',['80','3306','22'],0],['¿Qué servicio usa normalmente 3306?',['MySQL','Apache HTTP','DNS'],0],['¿Qué herramienta administra la BD en XAMPP?',['phpMyAdmin','CSS','Git log'],0],['¿Qué registra Git?',['Historial de cambios','Sesiones PHP','Certificados automáticamente'],0]]
];
$items_despliegue=['Subir archivos por FTP/SFTP','Crear e importar la base de datos','Configurar credenciales y variables de entorno','Configurar dominio y DNS','Habilitar HTTPS','Desactivar errores públicos y probar copias de seguridad'];
```

### 8.4 · `includes/header.php`

**Responsabilidad.** Presentación común. Recibe opcionalmente $titulo y abre el documento HTML.

**Bloque 1.** Carga db.php, declara idioma, codificación y viewport. El meta csrf expone el token de esta sesión a app.js; no contiene la contraseña.

**Bloque 2.** El título se imprime mediante e(). El enlace CSS y el script se construyen con BASE_URL. defer permite que el script use los controles una vez analizado el HTML.

**Bloque 3.** Incluye nav.php y abre main. El cierre de main y del documento se delega a footer.php.

**Código para estudiar:**

```php
<?php require_once __DIR__.'/../config/db.php'; ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf" content="<?=e(token())?>"><title><?=e($titulo ?? 'Laboratorio de Ingeniería Web')?></title><link rel="stylesheet" href="<?=BASE_URL?>/assets/css/estilos.css"><script defer src="<?=BASE_URL?>/assets/js/app.js"></script></head><body><?php include __DIR__.'/nav.php'; ?><main>
```

### 8.5 · `includes/nav.php`

**Responsabilidad.** Navegación. Consulta alumno() para decidir qué controles mostrar.

**Bloque 1.** El enlace de marca lleva a la portada. El botón tema será conectado por JavaScript.

**Bloque 2.** Si hay usuario, imprime su nombre escapado. Solo presenta el enlace Docente cuando el rol es admin; docente.php también verifica ese permiso en el servidor.

**Bloque 3.** Salir es un formulario POST con token CSRF. Sin sesión se muestra el enlace de acceso y registro. Ocultar un enlace no es suficiente para proteger su destino.

**Código para estudiar:**

```php
<nav><a class="marca" href="<?=BASE_URL?>/index.php">◈ Laboratorio <small>Ingeniería Web</small></a><div><button id="tema" type="button" aria-label="Cambiar modo claro u oscuro">◐ Tema</button><?php if(alumno()): ?><span><?=e(alumno()['nombre'])?></span><?php if(alumno()['rol']==='admin'): ?><a href="<?=BASE_URL?>/docente.php">Docente</a><?php endif; ?><form action="<?=BASE_URL?>/logout.php" method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><button>Salir</button></form><?php else: ?><a href="<?=BASE_URL?>/login.php">Entrar / registrarse</a><?php endif; ?></div></nav>
```

### 8.6 · `includes/footer.php`

**Responsabilidad.** Presentación común. Cierra main, imprime el pie de página y cierra body y html. No consulta ni modifica datos.

**Bloque 1.** Se incluye al final de las páginas para mantener un documento HTML coherente y reutilizar el mismo pie.

**Código para estudiar:**

```php
</main><footer>Laboratorio de Ingeniería Web · Aprende, experimenta y comprueba.</footer></body></html>
```

### 8.7 · `index.php`

**Responsabilidad.** Página de entrada y progreso. Recibe una petición GET y la sesión del alumno, si existe.

**Bloque 1.** Carga configuración y contenido. Inicializa notas y aviso para que la página pueda presentarse aunque no haya resultados.

**Bloque 2.** Si hay alumno, la subconsulta selecciona MAX(id) agrupado por modulo_id y filtrado por alumno. El JOIN recupera esas filas; array_column construye un mapa módulo → puntaje.

**Bloque 3.** La cantidad de notas determina el progreso. array_sum dividido por count calcula la media solo si existen notas, evitando división por cero. number_format prepara su presentación.

**Bloque 4.** El foreach de contenidos crea las seis tarjetas. Cada enlace usa el slug; cada progress vale 0 o 1 según haya evaluación. Un catch presenta el fallo de conexión sin impedir el menú.

**Código para estudiar:**

```php
<?php
require __DIR__.'/config/db.php';require __DIR__.'/includes/contenido.php';$notas=[];$aviso='';
try{if(alumno()){$q=bd()->prepare('SELECT r.modulo_id,r.puntaje FROM resultados r JOIN (SELECT modulo_id,MAX(id) ultimo FROM resultados WHERE alumno_id=? GROUP BY modulo_id) u ON r.id=u.ultimo');$q->execute([alumno()['id']]);$notas=array_column($q->fetchAll(),'puntaje','modulo_id');}}catch(Throwable $error){$aviso=$error->getMessage();}
include __DIR__.'/includes/header.php'; ?>
<p class="eyebrow">APRENDE HACIENDO · PHP + MYSQL</p><h1>Laboratorio de<br>Ingeniería Web</h1><p class="suave">Del primer documento HTML a una aplicación con datos. Explora seis módulos, experimenta en tu servidor y comprueba lo aprendido.</p><?php if($aviso): ?><p class="aviso"><?=e($aviso)?></p><?php endif; ?>
<section><div class="grid"><div><strong><?=count($notas)?> / 6 módulos evaluados</strong><progress max="6" value="<?=count($notas)?>" aria-label="Módulos evaluados"></progress></div><div><strong>Nota media: <?=count($notas)?e(number_format(array_sum($notas)/count($notas),1)):'—'?> / 10</strong><p class="suave">Media de los últimos intentos. Un módulo está completado al registrar una evaluación.</p></div></div><?php if(!alumno()): ?><a href="login.php">Inicia sesión para guardar tu progreso</a><?php endif; ?></section>
<div class="grid"><?php foreach($contenidos as $id=>$c): ?><a class="tarjeta" href="<?=BASE_URL?>/modulos/<?=e($c[1])?>/"><span class="numero">0<?=e($id)?></span><h2><?=e($c[0])?></h2><p class="suave">Teoría · Laboratorio · 5 preguntas</p><progress max="1" value="<?=isset($notas[$id])?1:0?>" aria-label="Progreso de <?=e($c[0])?>"></progress><p><?=isset($notas[$id])?'Última nota: '.e($notas[$id]).'/10':'Evaluación pendiente'?> →</p></a><?php endforeach; ?></div><?php include __DIR__.'/includes/footer.php'; ?>
```

### 8.8 · `login.php`

**Responsabilidad.** Control y presentación de registro/acceso. Atiende GET para mostrar formularios y POST para procesarlos.

**Bloque 1.** Antes de operar valida CSRF. La presencia de registro decide el camino; ambos formularios comparten archivo.

**Bloque 2.** En registro valida nombre, email y contraseña; consulta duplicados; prepara INSERT y guarda password_hash. El rol no se acepta desde el formulario.

**Bloque 3.** En acceso transforma únicamente los alias de prueba, busca por email y comprueba password_verify. Si es correcto, regenera la sesión, quita el hash del arreglo, guarda usuario, cambia el token y redirige.

**Bloque 4.** El catch muestra mensajes comprensibles. La parte HTML tiene dos formularios, cada uno con su token; autocomplete orienta al navegador y los atributos de entrada proporcionan validación básica.

**Código para estudiar:**

```php
<?php
require __DIR__.'/config/db.php';$mensaje='';
try{if($_SERVER['REQUEST_METHOD']==='POST'){csrf();$clave=$_POST['password']??'';$email=trim($_POST['email']??'');
if(isset($_POST['registro'])){$nombre=trim($_POST['nombre']??'');if($nombre===''||mb_strlen($nombre)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190||strlen($clave)<8||strlen($clave)>72)throw new RuntimeException('Nombre válido, email válido y contraseña de 8 a 72 bytes.');
$q=bd()->prepare('SELECT id FROM alumnos WHERE email=?');$q->execute([$email]);if($q->fetch())throw new RuntimeException('Ese email ya está registrado.');$q=bd()->prepare('INSERT INTO alumnos(nombre,email,password) VALUES(?,?,?)');$q->execute([$nombre,$email,password_hash($clave,PASSWORD_DEFAULT)]);$mensaje='Registro completado. Ya puedes iniciar sesión.';
}else{
if(strlen($clave)>72)throw new RuntimeException('Credenciales incorrectas.');
// Los alias de acceso solo se permiten para los dos usuarios de prueba.
if(in_array($email,['admin','alumno']))$email.='@lab.local';$q=bd()->prepare('SELECT id,nombre,email,password,rol FROM alumnos WHERE email=?');$q->execute([$email]);$usuario=$q->fetch();if(!$usuario||!password_verify($clave,$usuario['password']))throw new RuntimeException('Credenciales incorrectas.');session_regenerate_id(true);unset($usuario['password']);$_SESSION['alumno']=$usuario;$_SESSION['csrf']=bin2hex(random_bytes(32));header('Location: '.BASE_URL.'/index.php');exit;}}}
catch(Throwable $error){error_log($error->getMessage());$mensaje=$error instanceof PDOException?'No se pudo registrar al alumno. Comprueba si el email ya existe.':$error->getMessage();}
$titulo='Acceso al laboratorio';include __DIR__.'/includes/header.php'; ?>
<h1>Tu espacio de aprendizaje</h1><?php if($mensaje): ?><p class="aviso" role="status"><?=e($mensaje)?></p><?php endif; ?><div class="grid"><section><h2>Iniciar sesión</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><label>Email o usuario de prueba <input name="email" required maxlength="190" autocomplete="username"></label><label>Contraseña <input type="password" name="password" required maxlength="72" autocomplete="current-password"></label><button>Entrar</button></form></section><section><h2>Registrarse</h2><form method="post"><input type="hidden" name="csrf" value="<?=e(token())?>"><input type="hidden" name="registro" value="1"><label>Nombre <input name="nombre" required maxlength="100" autocomplete="name"></label><label>Email <input name="email" type="email" required maxlength="190" autocomplete="email"></label><label>Contraseña <input name="password" type="password" required minlength="8" maxlength="72" autocomplete="new-password"></label><button>Crear cuenta</button></form></section></div><?php include __DIR__.'/includes/footer.php'; ?>
```

### 8.9 · `logout.php`

**Responsabilidad.** Salida de sesión. Solo admite POST; un acceso GET termina con 405.

**Bloque 1.** Valida CSRF, vacía el arreglo de sesión y destruye los datos de sesión del servidor. Después redirige al acceso.

**Bloque 2.** El archivo no imprime la plantilla: una redirección utiliza cabeceras. Si el token falla, termina con 403 y un mensaje.

**Código para estudiar:**

```php
<?php require __DIR__.'/config/config.php';if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit;}try{csrf();}catch(Throwable $error){http_response_code(403);exit('Token inválido');}$_SESSION=[];session_destroy();header('Location: '.BASE_URL.'/login.php');
```

### 8.10 · `docente.php`

**Responsabilidad.** Página protegida para el docente. Primero exige sesión y después comprueba rol admin.

**Bloque 1.** Un alumno autenticado recibe 403 y una página de acceso restringido. La comprobación se hace antes de consultar el historial.

**Bloque 2.** El SELECT combina resultados, alumnos y modulos con JOIN para sustituir IDs por nombres y títulos. ORDER BY r.id DESC muestra los intentos recientes primero.

**Bloque 3.** La tabla imprime cada valor con e(). Si no hay intentos, se muestra una fila informativa; si falla la consulta, se muestra el mensaje gestionado.

**Código para estudiar:**

```php
<?php
require __DIR__.'/config/db.php';exigir_login();if(alumno()['rol']!=='admin'){http_response_code(403);$titulo='Acceso restringido';include __DIR__.'/includes/header.php';echo '<h1>Acceso restringido al docente</h1>';include __DIR__.'/includes/footer.php';exit;}
$titulo='Panel del docente';include __DIR__.'/includes/header.php'; ?>
<h1>Panel del docente</h1><p>Historial de todos los intentos, ordenado desde el más reciente. Las notas usan una escala de 0 a 10.</p><section class="tabla"><table><thead><tr><th>Alumno</th><th>Email</th><th>Módulo</th><th>Nota</th><th>Fecha</th></tr></thead><tbody><?php try{$filas=bd()->query('SELECT a.nombre,a.email,m.titulo,r.puntaje,r.fecha FROM resultados r JOIN alumnos a ON a.id=r.alumno_id JOIN modulos m ON m.id=r.modulo_id ORDER BY r.id DESC')->fetchAll();foreach($filas as $fila){echo '<tr>';foreach($fila as $valor)echo '<td>'.e($valor).'</td>';echo '</tr>';}if(!$filas)echo '<tr><td colspan="5">Todavía no hay evaluaciones registradas.</td></tr>';}catch(Throwable $error){echo '<tr><td colspan="5">'.e($error->getMessage()).'</td></tr>';} ?></tbody></table></section><?php include __DIR__.'/includes/footer.php'; ?>
```

### 8.11 · `includes/modulo.php`

**Responsabilidad.** Controlador y plantilla compartida de los módulos. Recibe $modulo desde el archivo de entrada.

**Bloque 1.** Carga contenido y base. Selecciona $contenidos[$modulo] y establece título y mensaje.

**Bloque 2.** Si recibe POST, exige usuario y CSRF. Con evaluar consulta las cinco preguntas del módulo. Cada respuesta debe ser escalar, numérica e índice existente en opciones; si no, interrumpe el proceso.

**Bloque 3.** Compara cada índice con respuesta_correcta, calcula aciertos*2 y guarda INSERT en resultados. alumno_id viene de sesión, no de un campo manipulable.

**Bloque 4.** La teoría cambia entre línea temporal, tabla y flujo. laboratorios.php decide la actividad correspondiente. La evaluación consulta solo id, enunciado y opciones para dibujar radios, sin imprimir respuestas_correctas.

**Bloque 5.** Los radios usan respuesta[ID] para que PHP construya un arreglo. El formulario incluye evaluar=1 y CSRF. El catch muestra problemas como una base sin importar o preguntas incompletas.

**Código para estudiar:**

```php
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
```

### 8.12 · `includes/laboratorios.php`

**Responsabilidad.** Presentación de laboratorios. Una cadena if/elseif elige la sección según $modulo; no se ejecutan todas las ramas.

**Bloque 1.** Módulo 1: enlaces a tres ejemplos y file_get_contents de una lista fija de archivos. e() permite mostrar etiquetas y PHP como texto, sin ejecutarlos dentro del bloque pre.

**Bloque 2.** Módulo 2: formulario y pre con IDs que app.js utiliza. Módulo 3: controles de método/estado, cookies/sesión y guía HTTPS; JavaScript envía las operaciones a APIs.

**Bloque 3.** Módulo 4: enlace MPA y, si hay alumno, formulario/lista SPA. Módulo 5: consulta pasos por propietario; array_column crea un mapa de estados; foreach dibuja casillas checked según valores.

**Bloque 4.** Módulo 6: incluye la función diagnostico(), recorre sus resultados y aplica una clase de color. Incluye pasos de preparación y advertencias concretas de desarrollo/producción.

**Código para estudiar:**

```php
<?php if($modulo===1): ?>
<p>Las tres versiones presentan un saludo. Abre sus respuestas y compara el código usado para producirlas.</p><div class="grid">
<?php foreach(['estatico.html','dinamico.php','fetch.html'] as $archivo): ?><article class="tarjeta"><h3><?=e($archivo)?></h3><a target="_blank" href="<?=BASE_URL?>/modulos/01-evolucion/<?=e($archivo)?>">Abrir ejemplo ↗</a><details><summary>Ver código original</summary><pre><?=e(file_get_contents(__DIR__.'/../modulos/01-evolucion/'.$archivo))?></pre></details></article><?php endforeach; ?></div>
<?php elseif($modulo===2): ?><form id="form-procesar"><label>Tu nombre <input name="nombre" required maxlength="100"></label><button>Enviar al servidor</button></form><pre id="salida-procesar" aria-live="polite">Aquí aparecerán los pasos reales de la petición.</pre>
<?php elseif($modulo===3): ?><h3>Inspector HTTP</h3><p>El endpoint devuelve su petición real; no publica contraseñas ni el identificador de sesión. Solo muestra cookies de este laboratorio.</p><form id="form-http"><label>Método <select name="metodo"><option>GET</option><option>POST</option><option>PUT</option><option>DELETE</option></select></label><label>Estado de respuesta <select name="estado"><option>200</option><option>201</option><option>400</option><option>404</option><option>500</option></select></label><button>Inspeccionar petición</button></form><pre id="salida-http" aria-live="polite"></pre><h3>Cookies y sesiones</h3><form id="form-estado"><label>Valor <input name="valor" maxlength="100" value="Hola clase"></label><label>Operación <select name="accion"><option value="crear">Crear</option><option value="leer">Leer</option><option value="borrar">Borrar</option></select></label><button>Ejecutar</button></form><pre id="salida-estado" aria-live="polite"></pre><details><summary>Activar HTTPS local</summary><ol><li>Revisa en C:\xampp\apache\conf\httpd.conf que estén activos LoadModule ssl_module e Include conf/extra/httpd-ssl.conf.</li><li>En httpd-ssl.conf comprueba Listen 443 y las rutas del certificado y clave incluidos en XAMPP.</li><li>Reinicia Apache y abre <a href="https://localhost/lab-web">https://localhost/lab-web</a>.</li><li>El certificado local puede producir un aviso por ser autofirmado. En producción usa un certificado confiable. TLS cifra el tráfico; no corrige errores de seguridad del código.</li></ol></details>
<?php elseif($modulo===4): ?><p>Ambas versiones operan sobre tus mismas tareas. Necesitas iniciar sesión. Actualiza la otra vista para ver los cambios.</p><p><a class="boton" href="<?=BASE_URL?>/modulos/04-tipos-apps/mpa.php">Abrir CRUD MPA</a></p><?php if(alumno()): ?><h3>CRUD SPA · sin recargar</h3><form id="form-tarea"><label>Nueva tarea <input name="titulo" required maxlength="150"></label><button>Crear</button></form><div id="tareas"></div><p id="mensaje-tareas" role="status"></p><?php endif; ?>
<?php elseif($modulo===5): if(!alumno()): ?><p>Inicia sesión para guardar la checklist.</p><?php else: ?><form id="form-checklist"><?php try{$q=bd()->prepare('SELECT item,completado FROM checklist_despliegue WHERE alumno_id=?');$q->execute([alumno()['id']]);$guardados=array_column($q->fetchAll(),'completado','item');}catch(Throwable $error){$guardados=[];echo '<p>'.e($error->getMessage()).'</p>';}foreach($items_despliegue as $item): ?><label><input type="checkbox" name="items[]" value="<?=e($item)?>" <?=!empty($guardados[$item])?'checked':''?>><?=e($item)?></label><?php endforeach; ?><button>Guardar checklist</button></form><p id="salida-checklist" role="status"></p><?php endif; ?>
<?php elseif($modulo===6): require_once __DIR__.'/../api/diagnostico.php'; ?><div class="tabla"><table><tr><th>Comprobación</th><th>Resultado</th></tr><?php foreach(diagnostico() as [$nombre,$valor,$ok]): ?><tr><td><?=e($nombre)?></td><td class="<?=$ok===null?'suave':($ok?'bien':'mal')?>"><?=e($ok===null?'? ':($ok?'✓ ':'✕ '))?><?=e($valor)?></td></tr><?php endforeach; ?></table></div><h3>Preparación del puesto</h3><ol><li>Instala XAMPP en C:\xampp e inicia Apache y MySQL desde el panel.</li><li>Apache usa 80 y 443; MySQL, 3306. Si hay conflicto, identifica el proceso con netstat -ano y el Administrador de tareas. Detén el servicio conflictivo solo si sabes para qué sirve, o cambia el puerto y actualiza URL/configuración.</li><li>Abre http://localhost/phpmyadmin, importa sql/lab_web.sql y revisa config/config.php.</li><li>Abre el proyecto en VS Code. Para principiantes: PHP Intelephense para ayuda de código y PHP Debug si configuras Xdebug aparte.</li><li>Git básico: git init, git add ., git commit -m "Primer laboratorio". Evita versionar credenciales reales.</li></ol><p>La escritura y display_errors se muestran para practicar localmente. En producción desactiva display_errors y limita permisos. Este proyecto no necesita mod_rewrite para funcionar.</p><?php endif; ?>
```

### 8.13 · `modulos/01-evolucion/index.php`

**Responsabilidad.** Entrada del módulo 1. Asigna $modulo=1 y carga la plantilla compartida. El valor selecciona teoría, laboratorio y preguntas de Evolución.

**Código para estudiar:**

```php
<?php $modulo=1; require __DIR__.'/../../includes/modulo.php';
```

### 8.14 · `modulos/02-cliente-servidor/index.php`

**Responsabilidad.** Entrada del módulo 2. Asigna $modulo=2 y carga modulo.php. Es la petición de esta URL la que inicia el recorrido PHP; no se ejecuta por abrir otro módulo.

**Código para estudiar:**

```php
<?php $modulo=2; require __DIR__.'/../../includes/modulo.php';
```

### 8.15 · `modulos/03-http-https/index.php`

**Responsabilidad.** Entrada del módulo 3. Asigna $modulo=3; la plantilla muestra el inspector y el laboratorio de estado. Sus APIs se ejecutarán en peticiones adicionales.

**Código para estudiar:**

```php
<?php $modulo=3; require __DIR__.'/../../includes/modulo.php';
```

### 8.16 · `modulos/04-tipos-apps/index.php`

**Responsabilidad.** Entrada del módulo 4. Asigna $modulo=4 y reutiliza la plantilla. Presenta el CRUD SPA y el enlace al documento MPA separado.

**Código para estudiar:**

```php
<?php $modulo=4; require __DIR__.'/../../includes/modulo.php';
```

### 8.17 · `modulos/05-plataformas/index.php`

**Responsabilidad.** Entrada del módulo 5. Asigna $modulo=5. La plantilla combina comparativa, checklist y evaluación almacenada.

**Código para estudiar:**

```php
<?php $modulo=5; require __DIR__.'/../../includes/modulo.php';
```

### 8.18 · `modulos/06-entorno/index.php`

**Responsabilidad.** Entrada del módulo 6. Asigna $modulo=6. Permite abrir la explicación y el diagnóstico aunque la BD falle; las comprobaciones gestionan el error y la evaluación informa del problema.

**Código para estudiar:**

```php
<?php $modulo=6; require __DIR__.'/../../includes/modulo.php';
```

### 8.19 · `modulos/01-evolucion/estatico.html`

**Responsabilidad.** Documento estático: Apache entrega el archivo sin ejecutar PHP. El saludo y el párrafo son siempre los mismos.

**Bloque 1.** doctype declara HTML5; lang indica español; meta charset codifica el texto; title nombra la pestaña; h1 y p forman el contenido. Los ejemplos usan una estructura breve con etiquetas que HTML permite omitir.

**Código para estudiar:**

```html
<!doctype html><html lang="es"><meta charset="utf-8"><title>HTML estático</title><h1>Hola, clase</h1><p>Este archivo siempre entrega el mismo contenido. HTML describe la estructura.</p></html>
```

### 8.20 · `modulos/01-evolucion/dinamico.php`

**Responsabilidad.** Ejemplo dinámico. Carga config.php para disponer de zona horaria y e().

**Bloque 1.** date genera la hora en el servidor durante cada petición. PHP inserta ese texto en el HTML; el navegador recibe la hora calculada, no una instrucción date para ejecutar.

**Bloque 2.** Al recargar, se ejecuta otra petición y puede cambiar el valor. Ver código fuente muestra el resultado, mientras el laboratorio ofrece el código PHP original.

**Código para estudiar:**

```php
<?php require __DIR__.'/../../config/config.php'; ?>
<!doctype html><html lang="es"><meta charset="utf-8"><title>PHP dinámico</title><h1>Hola, clase</h1><p>Hora del servidor: <?=e(date('H:i:s'))?></p><p>PHP ejecuta date() antes de enviar HTML. Ver código fuente en el navegador muestra solo el resultado.</p></html>
```

### 8.21 · `modulos/01-evolucion/fetch.html`

**Responsabilidad.** Ejemplo del cliente. El navegador muestra primero Consultando la API y ejecuta el script incrustado.

**Bloque 1.** fetch usa una ruta relativa desde esta carpeta hasta api/saludo.php. El primer then revisa respuesta.ok y decodifica JSON; el siguiente actualiza textContent con mensaje y hora.

**Bloque 2.** catch muestra un fallo de red o HTTP. El HTML inicial y los datos llegaron en dos peticiones diferentes.

**Código para estudiar:**

```html
<!doctype html><html lang="es"><meta charset="utf-8"><title>JavaScript y fetch</title><h1>Hola, clase</h1><p id="mensaje">Consultando la API…</p><script>
// JavaScript corre en el navegador; PHP produce el JSON en el servidor.
fetch('../../api/saludo.php').then(respuesta=>{if(!respuesta.ok)throw new Error('Error HTTP');return respuesta.json();}).then(datos=>{document.querySelector('#mensaje').textContent=datos.mensaje+' · '+datos.hora;}).catch(error=>{document.querySelector('#mensaje').textContent=error.message;});
</script></html>
```

### 8.22 · `modulos/04-tipos-apps/mpa.php`

**Responsabilidad.** CRUD con documentos completos. Exige sesión antes de generar HTML.

**Bloque 1.** Para POST comprueba CSRF, extrae acción, valida ID y título según operación. Crear inserta propietario y título; actualizar cambia título y estado; eliminar borra. Las condiciones WHERE incluyen propietario.

**Bloque 2.** Después del cambio redirige a esta misma URL y termina. La siguiente petición GET consulta todas las tareas del usuario y genera un formulario por fila.

**Bloque 3.** Cada fila incluye ID y token ocultos. El botón Eliminar tiene formnovalidate porque borrar no requiere completar un título. El servidor aún valida acción e ID.

**Bloque 4.** e() protege la impresión del título en el atributo value. En errores gestionados se presenta el mensaje y se continúa con la pantalla.

**Código para estudiar:**

```php
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
```

### 8.23 · `api/comun.php`

**Responsabilidad.** Utilidades de las APIs. Carga sesión/BD y establece Content-Type JSON y Cache-Control no-store.

**Bloque 1.** responder asigna estado, serializa los datos como JSON, imprime y termina. JSON_UNESCAPED_UNICODE mantiene caracteres legibles; JSON_INVALID_UTF8_SUBSTITUTE sustituye secuencias inválidas.

**Bloque 2.** El manejador de excepciones registra el error, conserva un estado de error ya establecido y utiliza 400 o 500 si faltaba uno. Las RuntimeException contienen mensajes controlados; los fallos inesperados reciben un mensaje genérico.

**Bloque 3.** api_login devuelve 401 si falta sesión. cuerpo interpreta el texto de php://input como JSON y, si no obtiene arreglo, usa $_POST.

**Bloque 4.** metodo comprueba una lista permitida y añade Allow al responder 405. Cada endpoint decide cuándo llamar estas comprobaciones; no basta con incluir comun.php.

**Código para estudiar:**

```php
<?php
require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function responder($datos,int $codigo=200): never { http_response_code($codigo);echo json_encode($datos,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit; }
set_exception_handler(function(Throwable $error){error_log($error->getMessage());$codigo=http_response_code();if($codigo<400)$codigo=$error instanceof RuntimeException?400:500;responder(['error'=>$error instanceof RuntimeException?$error->getMessage():'No se pudo completar la operación.'],$codigo);});
function api_login(): void {if(!alumno()) responder(['error'=>'Inicia sesión.'],401);}
function cuerpo(): array { $datos=json_decode(file_get_contents('php://input'),true);return is_array($datos)?$datos:$_POST; }
function metodo(array $permitidos): void {if(!in_array($_SERVER['REQUEST_METHOD'],$permitidos)){header('Allow: '.implode(', ',$permitidos));responder(['error'=>'Método no permitido.'],405);}}
```

### 8.24 · `api/saludo.php`

**Responsabilidad.** API simple de lectura. Carga las utilidades, permite GET y devuelve mensaje y hora del servidor.

**Bloque 1.** No necesita alumno ni base de datos: es el primer ejemplo para entender JSON y fetch.

**Código para estudiar:**

```php
<?php require __DIR__.'/comun.php';metodo(['GET']);responder(['mensaje'=>'Hola desde una API PHP','hora'=>date('H:i:s')]);
```

### 8.25 · `api/procesar.php`

**Responsabilidad.** API de formulario. Admite POST y exige CSRF, pero no inicio de sesión porque el ejercicio no guarda datos del alumno.

**Bloque 1.** Obtiene nombre, recorta espacios y valida de 1 a 100 caracteres. mb_strlen cuenta caracteres multibyte, útil para texto en español.

**Bloque 2.** Devuelve pasos explicativos, método/nombre recibidos y saludo. Los pasos describen el proceso didáctico, no registros medidos por Apache. No ejecuta consulta MySQL.

**Código para estudiar:**

```php
<?php
require __DIR__.'/comun.php';metodo(['POST']);csrf();$nombre=trim($_POST['nombre']??'');
if($nombre===''||mb_strlen($nombre)>100) responder(['error'=>'Escribe un nombre de 1 a 100 caracteres.'],422);
responder(['pasos'=>['Cliente: JavaScript validó el formulario y envió POST.','Apache: entregó la petición a PHP.','PHP: recibió y validó el nombre.','PHP: construyó JSON.','Cliente: muestra esta respuesta.'],'recibido'=>['metodo'=>$_SERVER['REQUEST_METHOD'],'nombre'=>$nombre],'respuesta'=>'Hola, '.$nombre,'nota'=>'Esta operación no necesita una consulta MySQL.']);
```

### 8.26 · `api/http.php`

**Responsabilidad.** Inspector de peticiones. Admite GET, POST, PUT y DELETE; los métodos distintos de GET exigen CSRF. El estado se elige solo entre los valores permitidos.

**Bloque 1.** getallheaders obtiene cabeceras si el entorno dispone de la función. Se filtra una lista de cabeceras de interés y solo lab_demo entre las cookies.

**Bloque 2.** array_intersect_key selecciona campos de $_SERVER y de cookies. array_diff_key quita csrf de $_POST. No se devuelven Authorization ni la cookie de sesión.

**Bloque 3.** La respuesta incluye GET, POST, cuerpo interpretado y estado_simulado. responder utiliza ese estado como código HTTP real; JavaScript lo muestra incluso cuando no es 2xx.

**Código para estudiar:**

```php
<?php
require __DIR__.'/comun.php';metodo(['GET','POST','PUT','DELETE']);
if($_SERVER['REQUEST_METHOD']!=='GET') csrf();
$estado=(int)($_GET['estado']??200);if(!in_array($estado,[200,201,400,404,500])) responder(['error'=>'Estado no permitido'],422);
// Lista permitida: no exponer Authorization, cookies de sesión ni datos internos del servidor.
$cabeceras=function_exists('getallheaders')?getallheaders():[];$seguras=[];foreach($cabeceras as $k=>$v) if(in_array(strtolower($k),['accept','content-type','user-agent','host']))$seguras[$k]=$v;
$cookies=array_intersect_key($_COOKIE,['lab_demo'=>true]);
responder(['SERVER'=>array_intersect_key($_SERVER,array_flip(['REQUEST_METHOD','REQUEST_URI','SERVER_PROTOCOL','HTTPS'])),'cabeceras'=>$seguras,'GET'=>$_GET,'POST'=>array_diff_key($_POST,['csrf'=>true]),'cuerpo'=>$_SERVER['REQUEST_METHOD']==='GET'?null:cuerpo(),'COOKIE'=>$cookies,'estado_simulado'=>$estado,'nota'=>'Los estados de error son intencionales en este probador.'], $estado);
```

### 8.27 · `api/estado.php`

**Responsabilidad.** Operaciones de estado del laboratorio. Admite POST, valida CSRF y limita el valor a 100 caracteres.

**Bloque 1.** Crear emite Set-Cookie con expiración de una hora y escribe $_SESSION[demo]. Borrar emite una expiración pasada y elimina demo de la sesión. Leer conserva ambos valores.

**Bloque 2.** La cookie se limita a BASE_URL y es HttpOnly: se lee con PHP, no mediante document.cookie. Las operaciones no eliminan la sesión del alumno.

**Bloque 3.** El JSON distingue cookie_recibida (entrada actual) y sesion_actual (estado tras la operación); esa diferencia explica por qué la cookie se lee en otra petición.

**Código para estudiar:**

```php
<?php
require __DIR__.'/comun.php';metodo(['POST']);csrf();$accion=$_POST['accion']??'';$valor=trim($_POST['valor']??'');if(mb_strlen($valor)>100) responder(['error'=>'Máximo 100 caracteres'],422);
if($accion==='crear'){setcookie('lab_demo',$valor,['expires'=>time()+3600,'path'=>BASE_URL,'httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);$_SESSION['demo']=$valor;}
elseif($accion==='borrar'){setcookie('lab_demo','',['expires'=>time()-3600,'path'=>BASE_URL,'httponly'=>true,'samesite'=>'Lax']);unset($_SESSION['demo']);}
elseif($accion!=='leer')responder(['error'=>'Operación inválida'],422);
responder(['cookie_recibida'=>$_COOKIE['lab_demo']??null,'sesion_actual'=>$_SESSION['demo']??null,'nota'=>'Set-Cookie cambia la cookie del navegador; pulsa Leer para verla en la siguiente petición.']);
```

### 8.28 · `api/tareas.php`

**Responsabilidad.** API del CRUD. Comprueba método permitido e inicio de sesión; el propietario procede de alumno(), nunca de un ID de alumno enviado por el cliente.

**Bloque 1.** GET usa una consulta preparada y devuelve la lista. Es la única rama que termina antes de exigir CSRF porque es de lectura.

**Bloque 2.** POST y PUT validan título. POST inserta y devuelve el nuevo ID con estado 201. PUT y DELETE validan ID y comprueban si existe para el usuario; si no, responden 404.

**Bloque 3.** PUT solo acepta estados 0/1/true/false y actualiza. DELETE elimina. En ambas consultas el WHERE repite alumno_id como límite de autorización.

**Bloque 4.** El parámetro id identifica la tarea; completada determina su estado, no el progreso de evaluación de un módulo.

**Código para estudiar:**

```php
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
```

### 8.29 · `api/checklist.php`

**Responsabilidad.** Escritura de checklist. Exige POST, sesión y CSRF; carga los pasos permitidos desde contenido.php.

**Bloque 1.** Las casillas se reciben en items como arreglo. array_diff detecta valores que no están autorizados. La lista vacía permite desmarcar todos los pasos.

**Bloque 2.** beginTransaction inicia una unidad de trabajo. Una consulta preparada se ejecuta por cada paso; la clave única permite insertar o actualizar con ON DUPLICATE KEY UPDATE.

**Bloque 3.** commit confirma todos los cambios. Si una operación falla, rollBack revierte y se relanza el error para que lo gestione comun.php. El JSON confirma cuántos pasos están marcados.

**Código para estudiar:**

```php
<?php
require __DIR__.'/comun.php';require __DIR__.'/../includes/contenido.php';metodo(['POST']);api_login();csrf();$items=$_POST['items']??[];
if(!is_array($items)||array_diff($items,$items_despliegue))responder(['error'=>'Elementos inválidos'],422);
$pdo=bd();$pdo->beginTransaction();try{$q=$pdo->prepare('INSERT INTO checklist_despliegue(alumno_id,item,completado) VALUES(?,?,?) ON DUPLICATE KEY UPDATE completado=VALUES(completado)');foreach($items_despliegue as $item)$q->execute([alumno()['id'],$item,(int)in_array($item,$items)]);$pdo->commit();}catch(Throwable $error){$pdo->rollBack();throw $error;}responder(['mensaje'=>'Checklist guardada: '.count($items).' de '.count($items_despliegue).' pasos.']);
```

### 8.30 · `api/diagnostico.php`

**Responsabilidad.** Función compartida y endpoint JSON. diagnostico devuelve filas de tres datos: nombre, explicación y estado booleano o null.

**Bloque 1.** Comprueba PHP con version_compare y extensiones con extension_loaded. SELECT 1 verifica una conexión usable; el catch convierte el fallo en una fila roja.

**Bloque 2.** apache_get_modules consulta módulos cuando la función existe; si no existe, mod_rewrite se marca como no verificable. is_writable inspecciona permisos del directorio del proyecto.

**Bloque 3.** También consulta display_errors y la zona horaria efectiva de PHP. Verde aquí expresa la condición didáctica local; display_errors activo no es una recomendación para producción.

**Bloque 4.** La condición final compara SCRIPT_FILENAME con __FILE__: si se solicita directamente, emite JSON; si se incluye en laboratorios.php, solo deja disponible la función.

**Código para estudiar:**

```php
<?php
require_once __DIR__.'/../config/db.php';
function diagnostico(): array {
    $lista=[['PHP 8.x',PHP_VERSION,version_compare(PHP_VERSION,'8.0','>=')]];
    foreach(['pdo_mysql','mbstring','openssl','curl'] as $extension) $lista[]=[$extension,extension_loaded($extension)?'Disponible':'Ausente',extension_loaded($extension)];
    try { bd()->query('SELECT 1'); $lista[]=['MySQL','Conexión correcta',true]; } catch(Throwable $e) { $lista[]=['MySQL',$e->getMessage(),false]; }
    $apache=function_exists('apache_get_modules') ? in_array('mod_rewrite',apache_get_modules()) : null;
    $lista[]=['mod_rewrite',$apache===null?'No verificable desde CLI/FastCGI':($apache?'Activo':'Inactivo'),$apache];
    $lista[]=['Escritura del proyecto',is_writable(dirname(__DIR__))?'Permitida':'Denegada',is_writable(dirname(__DIR__))];
    $lista[]=['display_errors',ini_get('display_errors')?'Activo (solo desarrollo)':'Desactivado',!empty(ini_get('display_errors'))];
    $lista[]=['Zona horaria',date_default_timezone_get(),date_default_timezone_get()==='America/Bogota'];
    return $lista;
}
if(realpath($_SERVER['SCRIPT_FILENAME'] ?? '')===__FILE__) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(diagnostico(),JSON_UNESCAPED_UNICODE); }
```

### 8.31 · `assets/js/app.js`

**Responsabilidad.** Interacción del navegador. Obtiene el token del meta y establece /lab-web como ruta base.

**Bloque 1.** Lee el tema de localStorage y cambia la clase oscuro del elemento html. try/catch tolera que el almacenamiento local esté bloqueado. La sesión de PHP no guarda esta preferencia.

**Bloque 2.** peticion() combina opciones, adjunta CSRF, usa fetch, interpreta JSON y lanza Error si respuesta.ok es falsa. conectar() reutiliza el patrón submit → FormData → POST → mensaje para tres formularios.

**Bloque 3.** Los botones data-etapa cambian el texto de la línea temporal. animar recorre las cajas, añade/quita activo y deshabilita el botón mientras dura la representación.

**Bloque 4.** El inspector utiliza fetch directamente para poder mostrar respuestas 404/500 en vez de tratarlas como fracaso genérico. Muestra estado, cabeceras visibles para el navegador y JSON.

**Bloque 5.** cargarTareas consulta la lista y crea nodos mediante DOM. Cada fila conecta checkbox, Editar y Eliminar. operar envía JSON y vuelve a consultar; si falla, comunica el error y recupera la lista.

**Bloque 6.** Editar usa prompt con el título inicial; cancelar no guarda. Crear envía FormData, limpia el formulario y actualiza la lista. Solo se inicia este comportamiento si existe el contenedor tareas.

**Bloque 7.** textContent evita interpretar los títulos como HTML. JS da respuesta visual rápida; PHP conserva la autoridad sobre validación y permisos.

**Código para estudiar:**

```javascript
'use strict';
// textContent muestra datos como texto: nunca interpreta HTML recibido de la API.
const base='/lab-web';
const csrf=document.querySelector('meta[name="csrf"]').content;
const raiz=document.documentElement;
try{raiz.classList.toggle('oscuro',localStorage.getItem('tema')==='oscuro');}catch(error){}
document.querySelector('#tema')?.addEventListener('click',()=>{raiz.classList.toggle('oscuro');try{localStorage.setItem('tema',raiz.classList.contains('oscuro')?'oscuro':'claro');}catch(error){}});
async function peticion(ruta,opciones={}){
 const respuesta=await fetch(base+'/api/'+ruta,{...opciones,headers:{'X-CSRF-Token':csrf,...opciones.headers}});
 const datos=await respuesta.json();if(!respuesta.ok)throw new Error(datos.error||'Error HTTP '+respuesta.status);return datos;
}
function conectar(id,salida,ruta){document.querySelector(id)?.addEventListener('submit',async evento=>{evento.preventDefault();const destino=document.querySelector(salida);destino.textContent='Enviando…';try{destino.textContent=JSON.stringify(await peticion(ruta,{method:'POST',body:new FormData(evento.target)}),null,2);}catch(error){destino.textContent=error.message;}});}
conectar('#form-procesar','#salida-procesar','procesar.php');conectar('#form-estado','#salida-estado','estado.php');conectar('#form-checklist','#salida-checklist','checklist.php');
document.querySelectorAll('[data-etapa]').forEach(boton=>boton.addEventListener('click',()=>{document.querySelector('#detalle-etapa').textContent=boton.textContent+': '+boton.dataset.etapa;}));
document.querySelector('#animar')?.addEventListener('click',async evento=>{evento.target.disabled=true;for(const paso of document.querySelectorAll('.flujo span')){paso.classList.add('activo');await new Promise(resolver=>setTimeout(resolver,550));paso.classList.remove('activo');}evento.target.disabled=false;});
document.querySelector('#form-http')?.addEventListener('submit',async evento=>{evento.preventDefault();const datos=new FormData(evento.target);const metodo=datos.get('metodo');const opciones={method:metodo,headers:{'X-CSRF-Token':csrf}};if(metodo!=='GET'){opciones.headers['Content-Type']='application/x-www-form-urlencoded';opciones.body='mensaje=Hola+HTTP';}try{const respuesta=await fetch(base+'/api/http.php?estado='+encodeURIComponent(datos.get('estado'))+'&ejemplo=clase',opciones);document.querySelector('#salida-http').textContent=JSON.stringify({estado_real:respuesta.status,cabeceras_respuesta:Object.fromEntries(respuesta.headers.entries()),datos:await respuesta.json()},null,2);}catch(error){document.querySelector('#salida-http').textContent=error.message;}});
const listado=document.querySelector('#tareas');
async function cargarTareas(){try{const tareas=await peticion('tareas.php');listado.replaceChildren();for(const tarea of tareas){const fila=document.createElement('div');fila.className='tarea';const estado=document.createElement('input');estado.type='checkbox';estado.checked=!!Number(tarea.completada);estado.setAttribute('aria-label','Completar '+tarea.titulo);const texto=document.createElement('span');texto.textContent=tarea.titulo;const editar=document.createElement('button');editar.textContent='Editar';const borrar=document.createElement('button');borrar.textContent='Eliminar';const operar=async(metodo,datos)=>{try{await peticion('tareas.php',{method:metodo,headers:{'Content-Type':'application/json'},body:JSON.stringify(datos)});await cargarTareas();document.querySelector('#mensaje-tareas').textContent='Cambio guardado.';}catch(error){document.querySelector('#mensaje-tareas').textContent=error.message;await cargarTareas();}};estado.addEventListener('change',()=>operar('PUT',{id:tarea.id,titulo:tarea.titulo,completada:estado.checked?1:0}));editar.addEventListener('click',()=>{const titulo=prompt('Nuevo título',tarea.titulo);if(titulo!==null){if(!titulo.trim()||titulo.length>150){document.querySelector('#mensaje-tareas').textContent='Título de 1 a 150 caracteres.';return;}operar('PUT',{id:tarea.id,titulo,completada:Number(tarea.completada)});}});borrar.addEventListener('click',()=>operar('DELETE',{id:tarea.id}));fila.append(estado,texto,editar,borrar);listado.append(fila);}}catch(error){document.querySelector('#mensaje-tareas').textContent=error.message;}}
if(listado){cargarTareas();document.querySelector('#form-tarea').addEventListener('submit',async evento=>{evento.preventDefault();try{await peticion('tareas.php',{method:'POST',body:new FormData(evento.target)});evento.target.reset();await cargarTareas();}catch(error){document.querySelector('#mensaje-tareas').textContent=error.message;}});}
```

### 8.32 · `assets/css/estilos.css`

**Responsabilidad.** Presentación visual. No ejecuta consultas ni calcula notas; aplica estilo al documento y a las clases producidas por PHP/JS.

**Bloque 1.** :root define la paleta clara y color-scheme. html.oscuro redefine la misma paleta. La clase se cambia desde app.js.

**Bloque 2.** El selector universal aplica border-box. body, main y nav definen tipografía, fondo, ancho máximo y navegación. .grid usa columnas adaptables con auto-fit/minmax.

**Bloque 3.** .tarjeta y section definen paneles; :hover da una respuesta visual. Inputs, botones y tablas comparten estilos; pre ajusta líneas largas. .flujo .activo indica el paso de la animación.

**Bloque 4.** .bien, .mal y .suave diferencian estados con texto y color. progress usa el valor ya calculado, no lo determina. @media cambia navegación y espaciado por debajo de 650px.

**Código para estudiar:**

```css
:root{color-scheme:light;--fondo:#f3f6fb;--panel:#fff;--texto:#17243a;--suave:#52627a;--borde:#d7dfeb;--acento:#295acc}html.oscuro{color-scheme:dark;--fondo:#101827;--panel:#1b273b;--texto:#eef3ff;--suave:#b3c1d9;--borde:#3b4960;--acento:#92b3ff}*{box-sizing:border-box}body{margin:0;background:var(--fondo);color:var(--texto);font:16px/1.65 system-ui,sans-serif}main{max-width:1150px;margin:auto;padding:40px 24px}nav{display:flex;justify-content:space-between;align-items:center;padding:18px 5%;background:var(--panel);border-bottom:1px solid var(--borde);gap:20px}nav div{display:flex;gap:18px;align-items:center;flex-wrap:wrap}nav form{margin:0}.marca{font-weight:800;text-decoration:none}.marca small{display:block;color:var(--suave)}a{color:var(--acento)}h1{font-size:clamp(2rem,5vw,3.5rem);line-height:1.15;letter-spacing:-.04em}h2{margin-top:0}p{max-width:85ch}.eyebrow{color:var(--acento);font-weight:700;letter-spacing:.1em}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px}.tarjeta,section{background:var(--panel);border:1px solid var(--borde);border-radius:18px;padding:26px;margin-bottom:22px}.tarjeta{display:block;text-decoration:none;color:var(--texto)}.tarjeta:hover{border-color:var(--acento);transform:translateY(-2px)}.numero{font-size:30px;color:var(--acento);font-weight:800}.suave,footer{color:var(--suave)}button,.boton{background:var(--acento);color:var(--fondo);border:0;border-radius:8px;padding:10px 16px;font:inherit;cursor:pointer;text-decoration:none;display:inline-block}button:disabled{opacity:.6}input,select,textarea{font:inherit;background:var(--fondo);color:var(--texto);border:1px solid var(--borde);border-radius:7px;padding:9px;max-width:100%}label{display:block;margin:10px 0}input[type=checkbox],input[type=radio]{margin-right:8px}pre{white-space:pre-wrap;overflow-wrap:anywhere;padding:20px;background:var(--fondo);border-radius:10px;max-height:480px;overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:12px;border-bottom:1px solid var(--borde)}.tabla{overflow:auto}.flujo{display:flex;gap:8px;flex-wrap:wrap}.flujo span{padding:14px;border:1px solid var(--borde);border-radius:10px}.flujo .activo{background:var(--acento);color:var(--fondo)}.bien{color:#19854e}.mal{color:#cc3646}.aviso{padding:16px;border-left:4px solid var(--acento);background:var(--panel)}progress{width:100%;accent-color:var(--acento)}footer{text-align:center;padding:30px}fieldset{border:1px solid var(--borde);border-radius:10px;margin:18px 0;padding:18px}.tarea{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0}.tarea span{flex:1}.linea{display:flex;flex-wrap:wrap;gap:10px}.linea button{background:var(--fondo);color:var(--acento);border:1px solid var(--borde)}@media(max-width:650px){nav{align-items:flex-start;flex-direction:column}main{padding:24px 16px}section{padding:18px}}
```

### 8.33 · `sql/generar.php`

**Responsabilidad.** Utilidad de desarrollo. Se ejecuta con PHP para producir lab_web.sql; no es parte del recorrido normal de un alumno.

**Bloque 1.** Carga semillas desde contenido.php. literal envuelve un valor SQL entre comillas simples y duplica comillas internas de estas semillas controladas; la aplicación usa PDO para entradas de usuarios.

**Bloque 2.** Construye las tablas con claves y relaciones. Recorre módulos y preguntas para INSERT IGNORE con IDs fijos. json_encode convierte opciones a texto JSON.

**Bloque 3.** Genera hashes nuevos de las contraseñas de prueba y crea una tarea inicial si la tabla aún está vacía. file_put_contents escribe el SQL.

**Bloque 4.** Cambiar semillas no cambia automáticamente la BD. Además INSERT IGNORE conserva IDs existentes; para actualizar preguntas ya importadas se necesitan UPDATE deliberados.

**Código para estudiar:**

```php
<?php
// Generador de desarrollo; el SQL resultante se importa directamente en phpMyAdmin.
require __DIR__.'/../includes/contenido.php';
function literal($v) { return "'".str_replace("'","''",$v)."'"; }
$sql="CREATE DATABASE IF NOT EXISTS lab_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE lab_web;\n";
$sql.="CREATE TABLE IF NOT EXISTS alumnos (id INT AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,rol ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP);\n";
$sql.="CREATE TABLE IF NOT EXISTS modulos (id INT PRIMARY KEY,titulo VARCHAR(150) NOT NULL,slug VARCHAR(100) NOT NULL,orden INT NOT NULL);\nCREATE TABLE IF NOT EXISTS preguntas (id INT PRIMARY KEY,modulo_id INT NOT NULL,enunciado TEXT NOT NULL,opciones JSON NOT NULL,respuesta_correcta INT NOT NULL,FOREIGN KEY(modulo_id) REFERENCES modulos(id));\nCREATE TABLE IF NOT EXISTS resultados (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,modulo_id INT NOT NULL,puntaje DECIMAL(4,2) NOT NULL,fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(alumno_id) REFERENCES alumnos(id),FOREIGN KEY(modulo_id) REFERENCES modulos(id));\n";
$sql.="CREATE TABLE IF NOT EXISTS tareas (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,titulo VARCHAR(150) NOT NULL,completada BOOLEAN NOT NULL DEFAULT 0,creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(alumno_id) REFERENCES alumnos(id));\nCREATE TABLE IF NOT EXISTS checklist_despliegue (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,item VARCHAR(150) NOT NULL,completado BOOLEAN NOT NULL DEFAULT 0,UNIQUE KEY alumno_item(alumno_id,item),FOREIGN KEY(alumno_id) REFERENCES alumnos(id));\n";
foreach($contenidos as $id=>$c) $sql.='INSERT IGNORE INTO modulos VALUES ('.$id.','.literal($c[0]).','.literal($c[1]).','.$id.");\n";
$id=1;foreach($preguntas_semilla as $mod=>$lista) foreach($lista as $p) $sql.='INSERT IGNORE INTO preguntas VALUES ('.$id++.','.$mod.','.literal($p[0]).','.literal(json_encode($p[1],JSON_UNESCAPED_UNICODE)).','.$p[2].");\n";
foreach(['admin','alumno'] as $nombre) $sql.='INSERT IGNORE INTO alumnos(nombre,email,password,rol) VALUES ('.literal($nombre).','.literal($nombre.'@lab.local').','.literal(password_hash($nombre.'123',PASSWORD_DEFAULT)).','.literal($nombre==='admin'?'admin':'alumno').");\n";
$sql.="INSERT INTO tareas(alumno_id,titulo) SELECT id,'Comparar el CRUD MPA y SPA' FROM alumnos WHERE email='alumno@lab.local' AND NOT EXISTS (SELECT 1 FROM tareas);\n";
file_put_contents(__DIR__.'/lab_web.sql',$sql);
```

### 8.34 · `sql/lab_web.sql`

**Responsabilidad.** Archivo de instalación para importar en phpMyAdmin. MySQL lo ejecuta por sentencias, no Apache ni JavaScript.

**Bloque 1.** CREATE DATABASE y USE crean/seleccionan lab_web. CREATE TABLE IF NOT EXISTS añade estructuras ausentes sin borrar tablas.

**Bloque 2.** Las tablas añaden password y rol a alumnos, y alumno_id a tareas para acceso y propiedad. Las claves foráneas conectan las entidades; la checklist tiene clave única compuesta.

**Bloque 3.** INSERT IGNORE carga seis módulos, treinta preguntas y dos cuentas de prueba sin duplicar sus claves. Los hashes se crearon con password_hash; las contraseñas conocidas solo sirven en este entorno local.

**Bloque 4.** El INSERT SELECT final obtiene el ID del alumno de prueba para la tarea inicial. Este SQL no es un sistema de migraciones: si ya existe una tabla con un esquema diferente, IF NOT EXISTS no la transforma.

**Código para estudiar:**

```sql
CREATE DATABASE IF NOT EXISTS lab_web CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lab_web;
CREATE TABLE IF NOT EXISTS alumnos (id INT AUTO_INCREMENT PRIMARY KEY,nombre VARCHAR(100) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password VARCHAR(255) NOT NULL,rol ENUM('alumno','admin') NOT NULL DEFAULT 'alumno',fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE IF NOT EXISTS modulos (id INT PRIMARY KEY,titulo VARCHAR(150) NOT NULL,slug VARCHAR(100) NOT NULL,orden INT NOT NULL);
CREATE TABLE IF NOT EXISTS preguntas (id INT PRIMARY KEY,modulo_id INT NOT NULL,enunciado TEXT NOT NULL,opciones JSON NOT NULL,respuesta_correcta INT NOT NULL,FOREIGN KEY(modulo_id) REFERENCES modulos(id));
CREATE TABLE IF NOT EXISTS resultados (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,modulo_id INT NOT NULL,puntaje DECIMAL(4,2) NOT NULL,fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(alumno_id) REFERENCES alumnos(id),FOREIGN KEY(modulo_id) REFERENCES modulos(id));
CREATE TABLE IF NOT EXISTS tareas (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,titulo VARCHAR(150) NOT NULL,completada BOOLEAN NOT NULL DEFAULT 0,creada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(alumno_id) REFERENCES alumnos(id));
CREATE TABLE IF NOT EXISTS checklist_despliegue (id INT AUTO_INCREMENT PRIMARY KEY,alumno_id INT NOT NULL,item VARCHAR(150) NOT NULL,completado BOOLEAN NOT NULL DEFAULT 0,UNIQUE KEY alumno_item(alumno_id,item),FOREIGN KEY(alumno_id) REFERENCES alumnos(id));
INSERT IGNORE INTO modulos VALUES (1,'Evolución del desarrollo web','01-evolucion',1);
INSERT IGNORE INTO modulos VALUES (2,'Arquitectura cliente-servidor','02-cliente-servidor',2);
INSERT IGNORE INTO modulos VALUES (3,'HTTP / HTTPS','03-http-https',3);
INSERT IGNORE INTO modulos VALUES (4,'Tipos de aplicaciones web','04-tipos-apps',4);
INSERT IGNORE INTO modulos VALUES (5,'Plataformas y despliegue','05-plataformas',5);
INSERT IGNORE INTO modulos VALUES (6,'Configuración del entorno','06-entorno',6);
INSERT IGNORE INTO preguntas VALUES (1,1,'¿Qué caracteriza a Web 1.0?','["Documentos estáticos","Sesiones de PHP","IA generativa"]',0);
INSERT IGNORE INTO preguntas VALUES (2,1,'¿Qué permite AJAX?','["Peticiones sin recargar todo","Eliminar HTTP","Ejecutar PHP en el navegador"]',0);
INSERT IGNORE INTO preguntas VALUES (3,1,'¿Dónde se ejecuta PHP?','["Navegador","Servidor","DNS"]',1);
INSERT IGNORE INTO preguntas VALUES (4,1,'¿Qué caracteriza a una SPA?','["Actualiza la interfaz con JavaScript","Solo admite HTML estático","No usa HTTP"]',0);
INSERT IGNORE INTO preguntas VALUES (5,1,'¿Qué aporta una PWA?','["Capacidades progresivas como instalación","Reemplaza MySQL","Necesita CGI"]',0);
INSERT IGNORE INTO preguntas VALUES (6,2,'¿Qué resuelve DNS?','["Nombres a direcciones","Consultas SQL","Contraseñas"]',0);
INSERT IGNORE INTO preguntas VALUES (7,2,'¿Qué hace Apache?','["Recibe peticiones HTTP","Dibuja botones","Ejecuta JavaScript del cliente"]',0);
INSERT IGNORE INTO preguntas VALUES (8,2,'¿Dónde corre JavaScript de la interfaz?','["MySQL","Navegador","DNS"]',1);
INSERT IGNORE INTO preguntas VALUES (9,2,'¿Qué conserva MySQL?','["Datos persistentes","Solo estilos","Certificados TLS"]',0);
INSERT IGNORE INTO preguntas VALUES (10,2,'¿Qué devuelve una API JSON?','["Datos estructurados","Código PHP para ejecutar en el cliente","Un servidor nuevo"]',0);
INSERT IGNORE INTO preguntas VALUES (11,3,'¿Qué método consulta recursos?','["GET","DELETE","PUT"]',0);
INSERT IGNORE INTO preguntas VALUES (12,3,'¿Qué significa 404?','["Éxito","Recurso no encontrado","Redirección"]',1);
INSERT IGNORE INTO preguntas VALUES (13,3,'¿Qué añade HTTPS?','["Cifrado TLS","Una base de datos","Elimina cookies"]',0);
INSERT IGNORE INTO preguntas VALUES (14,3,'¿Dónde se guardan los datos de sesión de PHP?','["Servidor","Solo en CSS","DNS"]',0);
INSERT IGNORE INTO preguntas VALUES (15,3,'¿Qué método se usa para eliminar un recurso?','["GET","DELETE","PUT"]',1);
INSERT IGNORE INTO preguntas VALUES (16,4,'¿Qué hace una MPA al enviar un formulario tradicional?','["Recarga un documento","No contacta el servidor","Ejecuta SQL en el cliente"]',0);
INSERT IGNORE INTO preguntas VALUES (17,4,'¿Qué usa este CRUD SPA?','["fetch y JSON","Composer","FTP"]',0);
INSERT IGNORE INTO preguntas VALUES (18,4,'¿Qué significa CRUD?','["Crear, leer, actualizar y eliminar","Cifrar rutas","Solo consultar"]',0);
INSERT IGNORE INTO preguntas VALUES (19,4,'¿Qué es un CMS?','["Gestor de contenidos","Certificado","Método HTTP"]',0);
INSERT IGNORE INTO preguntas VALUES (20,4,'¿Pueden SPA y MPA compartir tabla?','["Sí","Nunca","Solo sin PHP"]',0);
INSERT IGNORE INTO preguntas VALUES (21,5,'¿Qué ofrece un VPS?','["Control del servidor","Solo archivos HTML","Un navegador"]',0);
INSERT IGNORE INTO preguntas VALUES (22,5,'¿Qué necesita esta aplicación?','["PHP y MySQL","Solo alojamiento estático","Solo DNS"]',0);
INSERT IGNORE INTO preguntas VALUES (23,5,'¿Para qué sirve un dominio?','["Identificar un sitio con un nombre","Guardar tareas","Cifrar contraseñas"]',0);
INSERT IGNORE INTO preguntas VALUES (24,5,'¿Qué debe habilitarse al publicar?','["HTTPS","display_errors público","Contraseñas vacías"]',0);
INSERT IGNORE INTO preguntas VALUES (25,5,'¿Qué permite Docker?','["Entornos en contenedores","Eliminar validaciones","Reemplazar HTTP"]',0);
INSERT IGNORE INTO preguntas VALUES (26,6,'¿Qué extensión permite PDO con MySQL?','["pdo_mysql","gd","zip"]',0);
INSERT IGNORE INTO preguntas VALUES (27,6,'¿Cuál es el puerto HTTP habitual?','["80","3306","22"]',0);
INSERT IGNORE INTO preguntas VALUES (28,6,'¿Qué servicio usa normalmente 3306?','["MySQL","Apache HTTP","DNS"]',0);
INSERT IGNORE INTO preguntas VALUES (29,6,'¿Qué herramienta administra la BD en XAMPP?','["phpMyAdmin","CSS","Git log"]',0);
INSERT IGNORE INTO preguntas VALUES (30,6,'¿Qué registra Git?','["Historial de cambios","Sesiones PHP","Certificados automáticamente"]',0);
INSERT IGNORE INTO alumnos(nombre,email,password,rol) VALUES ('admin','admin@lab.local','HASH_DE_PASSWORD_GENERADO','admin');
INSERT IGNORE INTO alumnos(nombre,email,password,rol) VALUES ('alumno','alumno@lab.local','HASH_DE_PASSWORD_GENERADO','alumno');
INSERT INTO tareas(alumno_id,titulo) SELECT id,'Comparar el CRUD MPA y SPA' FROM alumnos WHERE email='alumno@lab.local' AND NOT EXISTS (SELECT 1 FROM tareas);
```

### 8.35 · `README.md`

**Responsabilidad.** Documento operativo: requisitos, instalación, usuarios, duración de clases, seguridad, HTTPS y resolución de problemas.

**Bloque 1.** No se ejecuta como aplicación. Se incluye aquí porque forma parte del proyecto y complementa la explicación del código con los pasos necesarios para usarlo.

**Código para estudiar:**

```markdown
# Laboratorio de Ingeniería Web

Aplicación educativa para XAMPP: PHP 8.x puro, HTML, CSS, JavaScript y MySQL/MariaDB con PDO. No necesita Composer, Node ni servicios externos.

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
```

### 8.36 · `assets/img/.gitkeep`

Archivo vacío que permite conservar la carpeta de imágenes en Git. No contiene lógica ni una imagen. No se carga desde las páginas actuales.

## 9. Comprobación final de comprensión

Al terminar, deberías poder responder con el código delante:

- ¿Qué archivo comienza a ejecutarse al abrir cada URL?
- ¿Qué instrucciones se ejecutan en PHP y cuáles en el navegador?
- ¿Dónde aparece el token y dónde se verifica?
- ¿Qué consulta guarda cada operación y cómo comprueba el propietario?
- ¿Cómo vuelve un dato de MySQL a la pantalla?
- ¿Por qué MPA recarga el documento y SPA actualiza nodos?
- ¿Qué significa completar un módulo en la portada?

Si puedes seguir una petición desde su entrada hasta su respuesta y explicar sus validaciones, ya puedes leer el proyecto por responsabilidades y no solo por nombres de archivos.
