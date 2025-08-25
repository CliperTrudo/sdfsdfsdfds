# Tutorías – Reserva de Exámenes

[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%20or%20later-blue.svg)](LICENSE)

Este proyecto es un plugin de WordPress para gestionar la reserva de tutorías de examen. Se integra con **Google Calendar** y **Google Meet** para automatizar la creación de eventos y el envío de invitaciones a los alumnos.

## Índice
- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Uso](#uso)
- [Configuración](#configuración)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Notas](#notas)
- [Contribución](#contribución)
- [Licencia](#licencia)
- [Autores](#autores)

## Características
- **Formulario de reserva para alumnos** con verificación de DNI.
- **Selección de fecha de examen** y **tutor disponible**.
- Obtención de franjas horarias directamente desde el calendario de cada tutor.
- Creación automática de eventos en Google Calendar con enlace de Google Meet.
- Notificaciones por correo electrónico a los asistentes.
- Panel de administración para gestionar tutores y tokens de autenticación.
- Almacenamiento de reservas, tutores y tokens en la base de datos de WordPress.
- Importación masiva de tutores y alumnos de reserva mediante archivos XLSX de Excel.

## Requisitos
- WordPress compatible con **PHP 7.4** o superior.
- [Composer](https://getcomposer.org/) para instalar la biblioteca `google/apiclient`.
- Una aplicación de Google Cloud Platform con la API de Calendar habilitada.
- Archivo `credentials.json` de dicha aplicación (no incluido en el repositorio).

## Instalación
1. Copia este repositorio en la carpeta `wp-content/plugins/` de tu instalación de WordPress. La carpeta del plugin debe llamarse `tutorias-booking`.
2. Ejecuta `composer install` dentro de la carpeta del plugin para descargar las dependencias. (En entornos sin acceso a internet, asegúrate de disponer previamente de la carpeta `vendor/`).
3. Crea una carpeta `keys/` en la raíz del plugin y coloca en ella tu archivo `credentials.json`.
4. Activa el plugin desde el panel de administración de WordPress. Al activarlo se crean las tablas necesarias en la base de datos.

## Uso
1. En el menú de administración aparecerá la sección **Tutorías**. Desde ahí podrás:
   - Registrar tutores introduciendo su nombre y correo electrónico.
   - Importar listas de tutores o alumnos desde archivos XLSX.
   - Autorizar el acceso a su Google Calendar (OAuth).
   - Añadir alumnos a la tabla de reservas.
2. Los tutores deben crear eventos con el título **"DISPONIBLE"** en su Google Calendar para indicar las franjas que desean ofrecer.
3. En cualquier página o entrada de WordPress inserta el shortcode:
   ```
   [formulario_dni]
   ```
   Así los alumnos podrán verificar su DNI, elegir fecha de examen, seleccionar tutor y reservar una franja disponible.
   Por defecto, el formulario ocupa un ancho máximo de **450px**, pero puedes ajustarlo usando el atributo opcional
   `width`, por ejemplo: ` [formulario_dni width="400px"] ` o ` [formulario_dni width="50%"] `.

4. Para que los tutores conecten su calendario desde el frontend, inserta el shortcode:
   ```
   [tutor_connect]
   ```
   Tras introducir su correo, el tutor podrá autorizar el acceso a su Google Calendar.

## Configuración
- Coloca tu archivo `credentials.json` en la carpeta `keys/` para habilitar la integración con Google Calendar.
- Si necesitas otra zona horaria distinta de **Europe/Madrid**, actualízala en el código del plugin.
- Personaliza el ancho del formulario `[formulario_dni]` mediante el atributo `width` del shortcode.

## Estructura del proyecto
```
assets/      CSS y JavaScript del frontend y del área de administración.
includes/    Código PHP organizado en módulos:
  ├─ Core/           Activador del plugin y cargador de componentes.
  ├─ Admin/          Página y menú de administración.
  ├─ Frontend/       Shortcodes y manejadores AJAX.
  └─ Google/         Integración con Google Calendar.
templates/   Plantillas HTML para el frontend y el panel de administración.
tutorias-booking.php  Archivo principal del plugin.
```

## Notas
- El plugin utiliza *nonces* de WordPress para asegurar las solicitudes AJAX.
- Los logs de depuración se envían a `error_log` para facilitar el diagnóstico de problemas.

## Contribución
Las contribuciones son bienvenidas. Para colaborar:
1. Haz un fork del repositorio y crea una rama para tu mejora o corrección.
2. Instala las dependencias con `composer install`.
3. Envía un pull request describiendo los cambios propuestos.

## Licencia
Este proyecto se distribuye bajo la licencia **GPL-2.0-or-later**. Consulta el archivo [LICENSE](LICENSE) para más detalles.

## Autores
- Equipo IT de Versus
