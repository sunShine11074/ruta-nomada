@echo off
REM ============================================================
REM   actualizar.bat - Pon al dia la instalacion | Ruta Nomada
REM
REM   COMO USARLO: doble clic. Ya esta.
REM
REM   No hace falta escribir nada en ninguna terminal, ni acordarse
REM   de rutas, ni preocuparse de si es PowerShell o Git Bash. Este
REM   archivo se coloca solo en la carpeta del proyecto y usa las
REM   rutas completas de XAMPP.
REM
REM   QUE HACE
REM     1. Pone la base de datos al dia (basedatos/actualizar_bd.sql).
REM        Solo ANADE lo que falta: no borra usuarios ni planes, y se
REM        puede ejecutar dos veces sin problema.
REM     2. Lanza el diagnostico y deja la ventana abierta para leerlo.
REM
REM   Si instalaste XAMPP en otra carpeta, cambia las dos rutas de
REM   abajo (MYSQL y PHP) y guarda el archivo.
REM ============================================================

setlocal

set "MYSQL=C:\xampp\mysql\bin\mysql.exe"
set "PHP=C:\xampp\php\php.exe"
set "BASE=ruta_nomada"

REM Ir a la carpeta del proyecto (este archivo vive en herramientas\)
cd /d "%~dp0.."

echo.
echo ============================================================
echo   Ruta Nomada - actualizar instalacion
echo ============================================================
echo   Carpeta: %CD%
echo.

if not exist "%MYSQL%" (
    echo   [X] No encuentro MySQL en:
    echo       %MYSQL%
    echo.
    echo   Si instalaste XAMPP en otra carpeta, abre este archivo con
    echo   el Bloc de notas y corrige la linea que empieza por  set "MYSQL=
    echo.
    pause
    exit /b 1
)

if not exist "basedatos\actualizar_bd.sql" (
    echo   [X] No encuentro basedatos\actualizar_bd.sql
    echo.
    echo   Este archivo tiene que estar dentro de la carpeta
    echo   herramientas\ del proyecto. Si lo copiaste a otro sitio,
    echo   devuelvelo a su lugar.
    echo.
    pause
    exit /b 1
)

echo   1/2  Actualizando la base de datos...
echo.
"%MYSQL%" -u root %BASE% -e "source basedatos/actualizar_bd.sql"
if not errorlevel 1 (
    REM Deja viajes_usuario.plan_id en SET NULL para que se puedan borrar
    REM planes. Es idempotente: si ya esta, no hace nada.
    "%MYSQL%" -u root %BASE% -e "source basedatos/migrate_borrar_plan.sql"
)
if not errorlevel 1 (
    REM Las 16 rutinas del proyecto: funciones, procedimientos y
    REM triggers. Tambien idempotente: cada una lleva su DROP delante.
    "%MYSQL%" -u root %BASE% -e "source basedatos/rutinas.sql"
)
if errorlevel 1 (
    echo.
    echo   [X] MySQL devolvio un error.
    echo.
    echo   Lo mas comun: MySQL esta apagado. Abre el panel de XAMPP y
    echo   pulsa Start en la fila de MySQL; luego vuelve a ejecutar
    echo   este archivo.
    echo.
    pause
    exit /b 1
)

echo.
echo   Listo. Arriba deben verse:  5 columnas, 5 tablas, 3 columnas
echo   mas en plan_invitaciones y 2 del testigo de cambio; y de
echo   rutinas, FUNCTION 7, PROCEDURE 7 y TRIGGER 22.
echo.
echo   2/2  Comprobando la instalacion...
echo.

if not exist "%PHP%" (
    echo   [!] No encuentro PHP en %PHP%, me salto el diagnostico.
    echo.
    pause
    exit /b 0
)

"%PHP%" herramientas\diagnostico.php

echo.
echo ============================================================
echo   Si arriba pone  0 fallos,  recarga la pagina en el navegador.
echo   Si hay algun [X], cada uno dice que comando arregla lo suyo.
echo ============================================================
echo.
pause
