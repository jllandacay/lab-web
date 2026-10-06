# Guía para descargar el proyecto con Git

Esta guía explica cómo descargar el laboratorio desde GitHub y ejecutarlo localmente en Windows. Git descarga el código; XAMPP ejecuta PHP y MySQL.

## Requisitos

1. Una computadora con Windows.
2. [Git para Windows](https://git-scm.com/downloads/win).
3. [XAMPP](https://www.apachefriends.org/), que incluye Apache, PHP, MySQL/MariaDB y phpMyAdmin.
4. Conexión a Internet para descargar el repositorio.

## 1. Instalar Git

1. Descarga Git desde [git-scm.com/downloads/win](https://git-scm.com/downloads/win).
2. Ejecuta el instalador. Para una instalación normal, puedes conservar las opciones predeterminadas.
3. Abre PowerShell y comprueba la instalación:

```powershell
git --version
```

Debe aparecer un número de versión de Git.

## 2. Descargar el proyecto

1. Abre el menú Inicio, busca **PowerShell** y ábrelo.
2. Ejecuta estos comandos:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/jllandacay/lab-web.git
cd lab-web
```

El proyecto quedará en `C:\xampp\htdocs\lab-web`. Comprueba que descargaste sus archivos:

```powershell
Get-ChildItem
```

En la lista deben aparecer archivos como `index.php` y carpetas como `api`, `assets`, `config`, `modulos` y `sql`.

> Si `C:\xampp\htdocs` no existe, instala XAMPP primero. Si XAMPP está instalado en otra ubicación, cambia la ruta del comando `cd` para apuntar a su carpeta `htdocs`.

## 3. Iniciar Apache y MySQL

1. Abre **XAMPP Control Panel**.
2. Pulsa **Start** en las filas **Apache** y **MySQL**.
3. Comprueba que ambos servicios aparecen iniciados.

## 4. Importar la base de datos

1. En el navegador, abre [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Selecciona **Importar**.
3. Pulsa **Seleccionar archivo** y elige:

   `C:\xampp\htdocs\lab-web\sql\lab_web.sql`

4. Pulsa **Importar** o **Continuar**, según la versión de phpMyAdmin.
5. Espera el mensaje que confirma que la importación terminó correctamente. El archivo crea la base `lab_web` y sus tablas.

En la configuración inicial de XAMPP, el proyecto usa MySQL en `127.0.0.1` con usuario `root` y contraseña vacía. Si se cambió esa configuración en tu computadora, consulta al docente antes de editar `config/config.php`.

## 5. Abrir el laboratorio

Abre esta dirección en el navegador:

[http://localhost/lab-web](http://localhost/lab-web)

Puedes registrarte con tu nombre, correo y una contraseña propia. Para probar el acceso con cuentas de demostración del SQL:

| Usuario | Contraseña | Uso |
|---|---|---|
| `alumno` | `alumno123` | Alumno |
| `admin` | `admin123` | Docente |

Estas cuentas son únicamente para el laboratorio local. No uses esas contraseñas en otros sitios ni para información personal.

## 6. Actualizar el proyecto después

Si ya lo descargaste con Git, abre PowerShell y ejecuta:

```powershell
cd C:\xampp\htdocs\lab-web
git pull
```

`git pull` descarga los cambios publicados por el docente. Si modificaste archivos localmente, guarda una copia antes de actualizar; Git puede pedirte resolver conflictos.

## Problemas comunes

- **`git` no se reconoce como comando:** instala Git y cierra y vuelve a abrir PowerShell.
- **`destination path 'lab-web' already exists`:** la carpeta ya existe. Entra a ella con `cd C:\xampp\htdocs\lab-web` y ejecuta `git pull` si fue descargada con Git.
- **No se puede conectar a `localhost`:** comprueba que Apache y MySQL estén iniciados en el panel de XAMPP.
- **Error de conexión con la base de datos:** comprueba que MySQL esté iniciado y que importaste `sql/lab_web.sql` en phpMyAdmin.
- **Apache no inicia:** puede haber otro programa usando el puerto 80. Informa al docente o revisa la configuración de puertos de XAMPP.
