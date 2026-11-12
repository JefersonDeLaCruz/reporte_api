<div align="center">

# Reporte Ciudadano — API

### Backend REST de la app [Reporte Ciudadano](https://github.com/manuelmv15/ReporteCiudadano)

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=flat&logo=docker&logoColor=white)
![Firebase](https://img.shields.io/badge/FCM-FFCA28?style=flat&logo=firebase&logoColor=black)

</div>

---

## ¿Qué hace?

Es la API que usa la app Android Reporte Ciudadano. Guarda los reportes de problemas urbanos, recibe los votos de la comunidad y cambia el estado de cada reporte de forma automática según esos votos.

- **Autenticación** con correo y contraseña o con Google, usando tokens de Laravel Sanctum.
- **Recuperación de contraseña** por correo, con un código de 6 dígitos que vence en 60 minutos.
- **Reportes** con ubicación, categoría, descripción y foto opcional.
- **Votos** "Sigue ahí" y "Ya se resolvió", solo para personas que están a menos de 500 m del reporte.
- **Cambio de estado automático** según los votos y archivado de reportes viejos.
- **Puntaje y niveles** de confiabilidad para cada usuario.
- **Notificaciones push** con Firebase Cloud Messaging.
- **Endpoint para el mapa en vivo**, que devuelve los reportes que cambiaron recientemente.

## Reglas de los reportes

| Estado | Cuándo pasa |
|---|---|
| `pending` | Al crearse el reporte |
| `verified` | Con 5 votos "Sigue ahí" (3 si vota un usuario nivel Experto) |
| `resolved` | Cuando "Ya se resolvió" llega al 70 % de los votos, con un mínimo de 3 votos |
| `archived` | Los resueltos, 2 h después. Los demás, tras 24 h sin interacción |

El archivado lo hace el comando `reports:archive-stale`, que el servicio `scheduler` corre cada 5 minutos.

**Puntaje:** +10 al autor cuando su reporte se verifica o se resuelve (una sola vez), +2 por voto "Sigue ahí" acertado (también si votas después de que se verificó) y +5 por voto "Ya se resolvió" acertado. Cada premio se registra en la tabla `score_awards`, así que nunca se paga dos veces aunque retires el voto y vuelvas a votar.

**Niveles:** Nuevo (0), Colaborador (20 puntos), Guardián (100) y Experto (300).

**Tienda:** cada punto ganado suma a dos contadores: `score` (define el nivel, nunca baja) y `coins` (saldo para gastar). Comprar un marco de avatar, un título o un color de barra solo descuenta `coins`. El catálogo vive en la tabla `cosmetics` (ver [`CosmeticSeeder`](src/database/seeders/CosmeticSeeder.php)); marco y título equipados se ven en los reportes de cada usuario.

Los valores están como constantes en [`Report.php`](src/app/Models/Report.php) y [`User.php`](src/app/Models/User.php).

## Endpoints

Todas las rutas empiezan con `/api`. Las marcadas con 🔒 necesitan el encabezado `Authorization: Bearer <token>`.

`GET /api/docs` devuelve la documentación completa de cada endpoint en JSON, con el cuerpo esperado y la respuesta.

**Autenticación**

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/register` | Crear cuenta |
| POST | `/login` | Iniciar sesión |
| POST | `/auth/google` | Iniciar sesión con Google (`id_token`) |
| POST | `/forgot-password` | Enviar código de recuperación |
| POST | `/reset-password` | Cambiar la contraseña con el código |
| POST | `/logout` 🔒 | Cerrar sesión y revocar el token |

**Perfil**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/me` 🔒 | Datos del usuario, puntaje y nivel |
| PUT | `/me` 🔒 | Cambiar el nombre |
| POST | `/me/avatar` 🔒 | Subir foto de perfil |
| POST | `/me/fcm-token` 🔒 | Registrar el token de notificaciones |
| GET | `/me/reports` 🔒 | Mis reportes |
| GET | `/me/votes` 🔒 | Mis votos |
| PUT | `/me/cosmetics` 🔒 | Equipar o quitar cosméticos: `frame`, `title`, `theme` con un slug propio, o `null`/`""` para quitarlo |

**Tienda**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/shop` 🔒 | Catálogo con `owned`/`equipped`/`locked` por artículo, más `coins`, `score` y `level` |
| POST | `/shop/{slug}/purchase` 🔒 | Comprar. `201` ok, `409` ya lo tienes, `403` nivel insuficiente, `422` sin puntos suficientes |

**Reportes y votos**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/reports` | Listar reportes (filtros: `status`, `category_id`, `per_page`) |
| GET | `/reports/{id}` | Detalle. Con token, incluye el voto del usuario |
| GET | `/reports/heatmap` | Datos para mapa de calor |
| GET | `/reports/stream/changes` | Reportes que cambiaron desde `since` |
| POST | `/reports` 🔒 | Crear reporte |
| PUT | `/reports/{id}` 🔒 | Editar reporte propio |
| DELETE | `/reports/{id}` 🔒 | Retirar reporte propio |
| PATCH | `/reports/{id}/status` 🔒 | Cambiar estado manualmente (solo administradores, `users.is_admin`; reparte los mismos puntos que el cambio automático) |
| POST | `/reports/{id}/votes` 🔒 | Votar: `type` = `confirm` o `resolve`, más tu `latitude` y `longitude` |
| DELETE | `/reports/{id}/votes/{type}` 🔒 | Quitar mi voto |

Al votar, la API responde `201` si el voto se guardó, `409` si ya votaste ese reporte y `422` si estás a más de 500 m.

**Categorías**

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/categories` | Listar categorías |
| GET | `/categories/{id}` | Detalle |
| POST / PUT / DELETE | `/categories/...` 🔒 | Administrar categorías |

Categorías incluidas: Bache, Alumbrado público, Basura acumulada, Fuga de agua, Semáforo dañado e Inseguridad.

## Cómo levantarlo

### Requisitos

- Docker y Docker Compose

### Pasos

1. Clona el repositorio:

   ```bash
   git clone https://github.com/JefersonDeLaCruz/laravel_api.git
   cd laravel_api
   ```

2. Crea el archivo de entorno de Laravel:

   ```bash
   cp src/.env.example src/.env
   ```

   En `src/.env`, configura la base de datos para que use el contenedor de MySQL:

   ```env
   APP_URL=http://localhost:8082

   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=apidb
   DB_USERNAME=apiuser
   DB_PASSWORD=apipass
   ```

3. Levanta los contenedores. `cloudflared` solo hace falta en producción, así que en local levanta los demás:

   ```bash
   docker compose up -d --build app nginx db scheduler
   ```

4. Instala las dependencias, genera la clave y crea la base de datos con datos de prueba:

   ```bash
   docker exec api_app composer install
   docker exec api_app php artisan key:generate
   docker exec api_app php artisan migrate --seed
   docker exec api_app php artisan storage:link
   ```

La API queda en **http://localhost:8082/api**. Para comprobarlo, abre http://localhost:8082/api/categories.

### Usuarios de prueba

El seeder crea un usuario por nivel. La contraseña de cada uno es su mismo correo:

| Correo | Nivel |
|---|---|
| `nuevo@test.com` | Nuevo |
| `colaborador@test.com` | Colaborador |
| `guardian@test.com` | Guardián |
| `experto@test.com` | Experto |

También crea 200 reportes de ejemplo.

### Servicios de Docker

| Servicio | Para qué sirve | Puerto |
|---|---|---|
| `app` | PHP-FPM con Laravel | — |
| `nginx` | Servidor web | 8082 |
| `db` | MySQL 8.0 | 3307 |
| `scheduler` | Corre las tareas programadas (`schedule:work`) | — |
| `phpmyadmin` | Administrar la base de datos (opcional) | — |
| `cloudflared` | Túnel de Cloudflare para producción | — |

### Variables opcionales

Agrégalas en `src/.env` si las necesitas:

| Variable | Para qué sirve |
|---|---|
| `GOOGLE_CLIENT_ID` | Login con Google. Debe ser el mismo *Web client ID* que usa la app Android |
| `FIREBASE_CREDENTIALS_PATH` | Ruta, relativa a `src/`, del JSON de la cuenta de servicio de Firebase (por ejemplo `.firebase/service-account.json`). Sin esta variable la API funciona, pero no envía notificaciones push |
| `MAIL_*` | Servidor SMTP para los correos de recuperación de contraseña. Por defecto se escriben en el log |

En el `.env` de la raíz va `CLOUDFLARE_TUNNEL_TOKEN`, que solo se usa en producción.

## Estructura

```
laravel_api/
├── docker-compose.yml
├── Dockerfile                 # PHP 8.4 FPM con Composer
├── nginx/default.conf
└── src/                       # Proyecto Laravel
    ├── app/
    │   ├── Console/Commands/  # reports:archive-stale
    │   ├── Http/Controllers/API/
    │   ├── Models/            # User, Report, ReportVote, Category
    │   └── Services/          # NotificationService (Firebase)
    ├── database/
    │   ├── migrations/
    │   └── seeders/           # Categorías, usuarios y reportes de prueba
    └── routes/api.php
```

## Equipo

| Nombre | GitHub |
|---|---|
| Jeferson Alexis De La Cruz Ventura | [@JefersonDeLaCruz](https://github.com/JefersonDeLaCruz) |
| Carlos Manuel Meléndez Villatoro | [@manuelmv15](https://github.com/manuelmv15) |
