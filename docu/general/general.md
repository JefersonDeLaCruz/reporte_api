# General Documentation

## [2026-06-11] config/app.php — timezone cambiado a America/El_Salvador

### Archivos tocados
- `src/config/app.php` — `'timezone'` cambiado de `'UTC'` a `'America/El_Salvador'`

### Motivo
- El contenedor `api_db` (MySQL) ya corre en hora salvadoreña (`TZ=America/El_Salvador` en `docker-compose.yml`, `NOW()` correcto), pero Laravel generaba `created_at`/`updated_at` en UTC porque `config('app.timezone')` estaba en UTC. Esto causaba un desfase de 6 horas entre la hora real y los timestamps guardados.
- Se corrigieron también los datos existentes (-6h) — ver `docu/db/db.md`.

### Notas
- Se ejecutó `php artisan config:clear` para aplicar el cambio sin caché.
- Confirmado: `now()` en tinker devuelve la hora local correcta (CST, UTC-6) y coincide con `NOW()` de MySQL.

### TODOs / Próximos pasos
- [ ] Si en el futuro se agregan jobs/colas o lógica con `Carbon::now('UTC')` explícito, revisar que sigan siendo correctos con el nuevo timezone por defecto.

---

## [2026-06-16] Firebase Cloud Messaging (FCM) — Notificaciones Push

### Archivos tocados
- `.gitignore` — agregada carpeta `/.firebase` (credenciales privadas)
- `.env` — variables `FIREBASE_CREDENTIALS_PATH` y `FIREBASE_PROJECT_ID`
- `.firebase/firebase-credentials.json` — credenciales del proyecto Firebase (creadas manualmente)
- `app/Services/NotificationService.php` — nuevo servicio para enviar notificaciones
- `app/Http/Controllers/API/ProfileController.php` — nuevo método `updateFcmToken()`
- `app/Http/Controllers/API/ReportController.php` — integración de notificaciones en `store()` y `updateStatus()`
- `routes/api.php` — nueva ruta `POST /me/fcm-token`
- `app/Http/Controllers/API/DocsController.php` — documentación del endpoint FCM en JSON

### Configuración

**Instalación:**
```bash
composer require kreait/firebase-php
```

**Credenciales:**
1. Descargar JSON del proyecto Firebase desde Google Cloud Console
2. Guardar en `.firebase/firebase-credentials.json` (incluido en `.gitignore`)
3. Definir en `.env`:
   ```env
   FIREBASE_CREDENTIALS_PATH=.firebase/firebase-credentials.json
   FIREBASE_PROJECT_ID=reporte-bombayashi
   ```

### Flujo de Notificaciones

1. **Cliente Android:**
   - Obtiene `fcm_token` del Firebase SDK
   - Envía `POST /me/fcm-token` con el token tras login

2. **Backend automático:**
   - `POST /reports` → dispara `NotificationService::notifyNearbyUsers()` (todos con token)
   - `PATCH /reports/{id}/status` → dispara `NotificationService::notifyVoters()` (votantes)

3. **Datos en notificación:**
   - Reporte nuevo: `report_id, latitude, longitude, status, type: 'new_report'`
   - Cambio de estado: `report_id, new_status, previous_status`

### Manejo de Errores
- Si Firebase no está configurado, `NotificationService` captura la excepción en `__construct()` y no envía nada (`$messaging = null`).
- Fallos individuales al enviar a un usuario se loguean con `\Log::error()` pero no rompen el flujo.

### TODOs / Próximos pasos
- [ ] Implementar en Android: obtener FCM token y registrarlo en backend
- [ ] Procesar notificaciones recibidas y actualizar mapa sin reload
- [ ] Panel de preferencias de notificaciones (silenciar por categoría, etc.)
- [ ] Monitoreo de tasa de fallos de envío
- [ ] Considerar agregar data para deep-linking (abrir reporte directamente)

---

## [2026-06-21] welcome.blade.php — Landing page de la API

### Archivos tocados
- `resources/views/welcome.blade.php` — reescritura completa

### Cambios
- Eliminada la página genérica de Laravel (logo, "Let's get started", links a docs de Laravel).
- Reemplazada por una landing page oscura del proyecto con:
  - Nombre del proyecto (desde `APP_NAME`) y tagline descriptivo
  - Barra de URL base (`APP_URL/api`) con badge de autenticación Bearer
  - Tabla de endpoints agrupados por módulo (Auth, Perfil, Reportes, Votos, Categorías) con método HTTP, path y descripción
  - Indicador 🔒 en rutas protegidas por Sanctum
  - Tarjetas de referencia rápida: encabezado de auth, ciclo de vida del reporte (pending→verified→resolved→archived), tipos de voto, link a /api/docs
  - Footer con versión de Laravel y PHP
- Sin dependencia de Vite/Tailwind compilado: estilos inline en `<style>` tag.

### TODOs / Próximos pasos
- [ ] Si se agregan nuevos endpoints, actualizar las secciones en welcome.blade.php
