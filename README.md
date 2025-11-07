SISTEMA DE GESTIÓN CLÍNICA VETERINARIA: PETCARE
1. REQUISITOS PREVIOS
Para instalar y ejecutar la aplicación es necesario contar con un entorno de servidor web local que soporte PHP y MySQL.

Servidor Local: Se recomienda utilizar XAMPP, WAMP o MAMP.
Lenguaje: PHP 8.0 o superior.
Base de Datos: MySQL o MariaDB (incluido en XAMPP/WAMP).
Navegador: Cualquier navegador web moderno (Chrome, Firefox, Edge).

2. VARIABLES DE ENTORNO (CONFIGURACIÓN DE LA BD)
La aplicación utiliza un archivo de configuración para la conexión a la base de datos, ubicado en la carpeta CONFIG/database.php.
Si está utilizando un servido local como XAMPP, debe crear una base de datos llamada petcare_db y pegar el código proporcionado o importarlo dentro de la misma.

IMPORTANTE: Si su servidor MySQL tiene una contraseña, debe actualizar la variable DB_PASS en el archivo de configuración para evitar el error de conexión "parámetros incorrectos".

3. INSTALACIÓN DE LA BASE DE DATOS
Siga estos pasos para crear y cargar el esquema de datos:
1. Iniciar Servicios: Abra su panel de control (XAMPP/WAMP) y asegúrese de que los módulos Apache (Servidor Web) y MySQL/MariaDB (Base de Datos) estén corriendo.

2. Acceder a phpMyAdmin: Abra su navegador y navegue a http://localhost/phpmyadmin.

3. Crear la Base de Datos: Haga clic en la pestaña 'Bases de datos' o 'New'. Cree una base de datos con el nombre exacto: petcare_db.

4. Ejecutar Scripts SQL:
   - Paso A: Estructura: Copie y pegue el contenido completo del archivo sql/petcare_db.sql (creación de tablas). Ejecute el script.
   - Paso B: Datos Iniciales: Repita el proceso. Ejecute el script.
4. EJECUCIÓN DEL SISTEMA (BACKEND & FRONTEND)
1. Copiar el Proyecto: Copie toda la carpeta del repositorio (petcare) dentro del directorio de su servidor web (ej. C:\xampp\htdocs\).
2. Abrir en el Navegador: Abra su navegador web y navegue a la URL:
   http://localhost/petcare/login.php

El Front-End (HTML/CSS) y el Back-End (PHP/SQL) se ejecutarán automáticamente al acceder a la URL.
