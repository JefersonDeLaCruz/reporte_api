# 📚 API Documentation

**Base URL:** `http://localhost:8080/api` (desarrollo) | `https://api.manuelmv.net/api` (producción)

**Auth Method:** `Bearer Token` (Laravel Sanctum)

**Última actualización:** 2026-06-12

---

## 🔑 Autenticación

Todos los endpoints protegidos requieren un token Bearer en el header:

```
Authorization: Bearer {token}
```

### Obtener Token

Usar `/register` o `/login` para obtener un token. El token se incluye en la respuesta y debe ser guardado por el cliente.

---

## 📋 Índice de Endpoints

- [🔐 Autenticación](#autenticación)
- [👤 Perfil](#perfil)
- [📁 Categorías](#categorías)
- [📍 Reportes](#reportes)
- [⚡ Real-time & Streaming](#real-time--streaming)
- [🗳️ Sistema de Votos](#sistema-de-votos)

---

## 🔐 Autenticación

### POST /register

Registrar un nuevo usuario.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | No |
| **Body** | `{ name: string, email: string, password: string, password_confirmation: string }` |
| **Respuesta** | `201 Created` — `{ success: true, message: "...", token: "...", user: {...} }` |
| **Status** | [x] Implementado |

**Ejemplo de request:**
```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

---

### POST /login

Iniciar sesión con email y contraseña.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | No |
| **Body** | `{ email: string, password: string }` |
| **Respuesta** | `200 OK` — `{ success: true, token: "...", user: {...} }` |
| **Status** | [x] Implementado |

---

### POST /auth/google

Login/registro usando Google OAuth (id_token).

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | No |
| **Body** | `{ id_token: string (JWT de Google) }` |
| **Respuesta** | `200 OK` — `{ success: true, token: "...", user: {...} }` |
| **Status** | [x] Implementado |
| **Notas** | Verifica token con Google API. Crea usuario si no existe, actualiza google_id/avatar si ya tiene email registrado |

---

### POST /logout

Cerrar sesión (revocar token).

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | Sí (Bearer Token) |
| **Body** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, message: "Logged out successfully" }` |
| **Status** | [x] Implementado |

---

### GET /me

Obtener datos del usuario autenticado.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | Sí (Bearer Token) |
| **Body** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, user: { id, name, email, avatar_url, score, level, ... } }` |
| **Status** | [x] Implementado |
| **Notas** | `score` y `level` son columnas existentes en `users` y ya viajan en el modelo, sin necesidad de cambios adicionales |

---

### GET /users

Listar todos los usuarios (admin).

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | Sí (Bearer Token) |
| **Query** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, users: [...] }` |
| **Status** | [x] Implementado |

---

## 👤 Perfil

Endpoints para que el usuario autenticado gestione su propio perfil ("Mi Perfil" en el cliente Android).

### PUT /me

Actualizar el nombre del usuario autenticado.

| Campo | Detalle |
|-------|---------|
| **Método** | PUT |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ name: string (required, max:255) }` |
| **Respuesta** | `200 OK` — `{ success: true, message: "Perfil actualizado", user: { id, name, email, avatar_url, score, level, ... } }` |
| **Status** | [x] Implementado ✅ (2026-06-11) |

---

### POST /me/avatar

Subir o reemplazar la foto de perfil del usuario autenticado.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | Sí (Bearer Token) |
| **Content-Type** | `multipart/form-data` |
| **Body** | `{ avatar: File (image, max 5MB, required) }` |
| **Respuesta** | `200 OK` — `{ success: true, message: "Avatar actualizado", avatar_url: string }` |
| **Status** | [x] Implementado ✅ (2026-06-11) |
| **Notas** | La foto se guarda en `/storage/avatars/`. Si el usuario ya tenía un avatar subido localmente se elimina del disco antes de guardar el nuevo; los avatares externos (ej: foto de Google) no se borran |

---

### GET /me/reports

Listar los reportes creados por el usuario autenticado (paginado).

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | Sí (Bearer Token) |
| **Query** | `status?: string` (pending/verified/resolved/archived), `per_page?: integer (default: 15)` |
| **Respuesta** | `200 OK` — `{ success: true, reports: { current_page, data: [...], total, per_page, ... } }` |
| **Status** | [x] Implementado ✅ (2026-06-11) |
| **Notas** | Misma forma que `GET /reports`, pero filtrado por `user_id` del usuario autenticado (evita filtrado client-side) |

---

### GET /me/votes

Historial de votos del usuario autenticado (paginado).

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | Sí (Bearer Token) |
| **Query** | `per_page?: integer (default: 15)` |
| **Respuesta** | `200 OK` — `{ success: true, votes: { current_page, data: [...], total, per_page } }` |
| **Status** | [x] Implementado ✅ (2026-06-11) |
| **Notas** | Cada item incluye `report_id`, `type`, `created_at` y el objeto `report` con datos resumidos (`description`, `status`, `photo_path`, `votes_confirm`, `votes_resolve`, `category`) |

---

## 📁 Categorías

Las categorías agrupan los tipos de reportes (ej: Baches, Alumbrado público, Agua, etc.).

### GET /categories

Listar todas las categorías disponibles.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | No |
| **Query** | `active_only?: boolean` — filtrar solo categorías activas |
| **Respuesta** | `200 OK` — `{ success: true, categories: [...] }` |
| **Status** | [x] Implementado |

---

### GET /categories/{id}

Obtener detalle de una categoría específica.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | No |
| **Respuesta** | `200 OK` — `{ success: true, category: { id, name, slug, icon, active, created_at, updated_at } }` |
| **Status** | [x] Implementado |

---

### POST /categories

Crear nueva categoría.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ name: string, slug: string (unique), icon: string, active?: boolean (default: true) }` |
| **Respuesta** | `201 Created` — `{ success: true, message: "...", category: {...} }` |
| **Status** | [x] Implementado |

---

### PUT /categories/{id}

Actualizar una categoría.

| Campo | Detalle |
|-------|---------|
| **Método** | PUT |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ name?: string, slug?: string, icon?: string, active?: boolean }` |
| **Respuesta** | `200 OK` — `{ success: true, message: "...", category: {...} }` |
| **Status** | [x] Implementado |

---

### DELETE /categories/{id}

Eliminar una categoría.

| Campo | Detalle |
|-------|---------|
| **Método** | DELETE |
| **Auth** | Sí (Bearer Token) |
| **Body** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, message: "Category deleted" }` |
| **Status** | [x] Implementado |

---

## 📍 Reportes

Los reportes son los problemas/incidentes que los usuarios reportan en la plataforma (ej: bache en calle X, falla de alumbrado en zona Y).

### GET /reports

Listar reportes con paginación.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | No |
| **Query** | `status?: string` (pending/verified/resolved/archived), `category_id?: integer`, `per_page?: integer (default: 15)` |
| **Respuesta** | `200 OK` — `{ success: true, reports: { current_page, data: [...], total, per_page, ... } }` |
| **Status** | [x] Implementado |
| **Datos incluidos** | Cada reporte incluye relaciones con `user` (`id, name, avatar_url, score, level`) y `category` |
| **Notas** | `user.score`/`user.level` agregados ✅ (2026-06-11) para que el cliente Android dimensione el marcador del autor según su nivel (RF-28) |

---

### GET /reports/{id}

Obtener detalle completo de un reporte con información de votos.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | Opcional (detecta token Bearer automáticamente) |
| **Respuesta** | `200 OK` — `{ success: true, report: {...} }` |
| **Status** | [x] Implementado ✅ (2026-06-09) |
| **Campos importantes** | Ver tabla de respuesta abajo |

**Estructura de respuesta:**

```json
{
  "success": true,
  "report": {
    "id": 25,
    "latitude": "13.4950000",
    "longitude": "-88.1800000",
    "status": "pending",
    "description": "Descripción del problema",
    "user_id": 9,
    "photo_path": "reports/xyz.jpg",
    "category": {
      "id": 1,
      "name": "Bache",
      "slug": "bache",
      "icon": "pothole",
      "active": true
    },
    "user": {
      "id": 9,
      "name": "Creador",
      "avatar_url": "...",
      "score": 12,
      "level": "nuevo"
    },
    "votes": {
      "confirm": 1,
      "resolve": 0
    },
    "user_vote": "confirm",
    "user_voted_at": "2026-06-09T04:42:10+00:00",
    "created_at": "2026-06-09T04:39:34+00:00",
    "updated_at": "2026-06-09T04:39:34+00:00"
  }
}
```

**Campos especiales:**
- `user_vote`: `null` si no ha votado, `"confirm"` o `"resolve"` si votó
- `user_voted_at`: Timestamp ISO de cuándo votó (útil para ventana de 5 min editable)
- `votes`: Desglose de contadores de votos
- `user.score` / `user.level`: nivel del autor del reporte (`nuevo`/`colaborador`/`guardian`/`experto`), usado por el cliente para el tamaño del marcador (RF-28)

---

### POST /reports

Crear nuevo reporte.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ category_id: integer (required), latitude: numeric, longitude: numeric, description: string (max 500), photo?: File (image, max 5MB) }` |
| **Content-Type** | `multipart/form-data` (si incluye foto) o `application/json` |
| **Respuesta** | `201 Created` — `{ success: true, message: "Reporte creado", report: {...} }` |
| **Status** | [x] Implementado |
| **Notas** | Status inicial: `pending`. Foto se guarda en `/storage/reports/` |

---

### PUT /reports/{id}

Actualizar un reporte (solo el dueño).

| Campo | Detalle |
|-------|---------|
| **Método** | PUT |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ category_id?: integer, description?: string, photo?: File }` |
| **Respuesta** | `200 OK` — `{ success: true, message: "Reporte actualizado", report: {...} }` |
| **Status** | [x] Implementado |
| **Validación** | Solo el usuario que creó el reporte puede modificarlo (401 si no es dueño) |

---

### DELETE /reports/{id}

Eliminar un reporte (solo el dueño).

| Campo | Detalle |
|-------|---------|
| **Método** | DELETE |
| **Auth** | Sí (Bearer Token) |
| **Body** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, message: "Reporte eliminado" }` |
| **Status** | [x] Implementado |
| **Notas** | Elimina también la foto del reporte del disco |

---

### PATCH /reports/{id}/status

Cambiar estado de un reporte (ciclo de vida: pending → verified → resolved → archived).

| Campo | Detalle |
|-------|---------|
| **Método** | PATCH |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ status: "pending" | "verified" | "resolved" | "archived" }` |
| **Respuesta** | `200 OK` — `{ success: true, message: "Estado actualizado", report: {...} }` |
| **Status** | [x] Implementado |
| **Timestamps** | Actualiza `status_changed_at` y el timestamp específico (`verified_at`, `resolved_at`, `archived_at`) |
| **Notas** | Cambio manual; las transiciones automáticas por votos (`pending→verified→resolved`) se manejan aparte en `POST /reports/{id}/votes` ✅ (2026-06-11, ver Sistema de Votos) |

---

## ⚡ Real-time & Streaming

Obtener reportes creados/actualizados en tiempo real sin necesidad de hacer polling exhaustivo a todos los reportes. Útil para mantener el mapa actualizado dinámicamente.

### GET /reports/stream/changes

Obtener reportes creados o actualizados en los últimos N segundos.

| Campo | Detalle |
|-------|---------|
| **Método** | GET |
| **Auth** | No |
| **Query Parameters** | `since?: string (ISO 8601, default: últimos 5 min)`, `limit?: integer (default: 50, max: 1000)` |
| **Respuesta** | `200 OK` — `{ success: true, timestamp: string, count: integer, reports: [...] }` |
| **Status** | [x] Implementado ✅ (2026-06-12) |
| **Características** | Retorna reportes completos con todas las relaciones (category, user, votos) |

**Ejemplo de request:**

```bash
# Obtener cambios de los últimos 10 minutos
curl http://localhost:8080/api/reports/stream/changes?limit=50

# Obtener cambios desde una fecha específica
curl 'http://localhost:8080/api/reports/stream/changes?since=2026-06-12T19:40:00&limit=50'
```

**Ejemplo de respuesta:**

```json
{
  "success": true,
  "timestamp": "2026-06-12T19:47:00-06:00",
  "count": 2,
  "reports": [
    {
      "id": 26,
      "latitude": 13.5,
      "longitude": -88.18,
      "status": "verified",
      "description": "Bache grande en carretera",
      "category_id": 1,
      "category": {
        "id": 1,
        "name": "Bache",
        "slug": "bache",
        "icon": "pothole",
        "active": true
      },
      "user_id": 13,
      "user": {
        "id": 13,
        "name": "Juan Pérez",
        "avatar_url": "https://..."
      },
      "votes_confirm": 2,
      "votes_resolve": 0,
      "photo_path": "reports/xyz.jpg",
      "created_at": "2026-06-12T19:46:47-06:00",
      "updated_at": "2026-06-12T19:46:58-06:00"
    }
  ]
}
```

**Flujo esperado en cliente Android:**

1. **Inicializar conexión:**
   - Guardar el `timestamp` actual de la respuesta
   
2. **Polling periódico (cada 5-10 segundos):**
   ```
   GET /api/reports/stream/changes?since={último_timestamp}&limit=50
   ```

3. **Procesar resultados:**
   - Por cada reporte en el array:
     - Si el ID no existe en el mapa → agregar pin nuevo
     - Si el ID existe → actualizar pin (status, votes, etc)
   - Actualizar `último_timestamp` con el nuevo `timestamp` de la respuesta

4. **Inyectar en Mapbox:**
   - Crear `PointAnnotation` con coordenadas (latitude, longitude)
   - Asignar icono según `status` y `category`
   - Actualizar sin necesidad de recargar la pantalla completa

**Ventajas:**
- ✅ Reportes aparecen en tiempo real en el mapa
- ✅ Cambios de estado se reflejan automáticamente
- ✅ Votos se actualizan sin reload
- ✅ Bajo consumo de datos (solo cambios recientes)
- ✅ Compatible con conexiones lentas
- ✅ Sin dependencias WebSocket (usa polling simple)

**Arquitectura detrás:**
- Los eventos `ReportCreated` y `ReportStatusChanged` se disparan automáticamente
- Redis almacena los eventos en canal `reports`
- El endpoint consulta reportes creados/actualizados desde la fecha especificada
- Escalable para agregar canales geográficos en el futuro

---

## 🗳️ Sistema de Votos

Los usuarios pueden votar reportes para confirmar que el problema existe (confirm) o que fue resuelto (resolve).

### POST /reports/{id}/votes

Registrar un voto en un reporte.

| Campo | Detalle |
|-------|---------|
| **Método** | POST |
| **Auth** | Sí (Bearer Token) |
| **Body** | `{ type: "confirm" | "resolve", latitude: numeric, longitude: numeric }` |
| **Respuesta (201)** | `{ success: true, message: "Voto registrado", data: { type, user_id, report_id, votes_confirm, votes_resolve, status, created_at } }` |
| **Status** | [x] Implementado ✅ (2026-06-09), `status` agregado ✅ (2026-06-11) |

**Validaciones:**
- **422 Unprocessable Entity** — Usuario debe estar a <500m del reporte (validación Haversine)
- **409 Conflict** — Usuario ya votó ese reporte con ese tipo

**Ejemplo de respuesta exitosa:**

```json
{
  "success": true,
  "message": "Voto registrado",
  "data": {
    "type": "confirm",
    "user_id": 10,
    "report_id": 25,
    "votes_confirm": 1,
    "votes_resolve": 0,
    "status": "pending",
    "created_at": "2026-06-09T04:42:10+00:00"
  }
}
```

**Flujo esperado del cliente Android:**
1. Usuario vota → POST /reports/{id}/votes
2. Cliente recibe `data` con conteos actualizados
3. Cliente actualiza UI inmediatamente con `votes_confirm`, `votes_resolve` y `status`
4. Cliente guarda `user_vote` y `user_voted_at` localmente (opcional)
5. Cliente puede calcular ventana de 5 min desde `user_voted_at`
6. Si `status` cambió a `"verified"` o `"resolved"` respecto al valor previo, el cliente puede mostrar feedback (badge/animación) de transición automática

**Transiciones automáticas (RF-11/RF-12/RF-30):**

Tras registrar el voto, el backend evalúa automáticamente (`Report::evaluateAutoStatus()`):
- `pending → verified`: `votes_confirm >= 5`, o `votes_confirm >= 3` si alguno de los votantes `confirm` tiene `level = experto`
- `pending|verified → resolved`: `(votes_confirm + votes_resolve) >= 3` y `votes_resolve / total >= 0.7`

Estas transiciones disparan scoring (RF-27): dueño del reporte +10 al verificarse, votantes `confirm` +2 y votantes `resolve` +5 (ver `docu/db/db.md`).

---

### DELETE /reports/{id}/votes/{type}

Retirar un voto propio.

| Campo | Detalle |
|-------|---------|
| **Método** | DELETE |
| **Auth** | Sí (Bearer Token) |
| **Path Parameters** | `type`: "confirm" o "resolve" |
| **Body** | N/A |
| **Respuesta** | `200 OK` — `{ success: true, message: "Voto retirado", report: {...} }` |
| **Status** | [x] Implementado |
| **Efecto** | Decrementa el contador `votes_confirm` o `votes_resolve` |

---

## 📊 Status Codes

| Code | Significado | Casos |
|------|-------------|-------|
| `200 OK` | Operación exitosa (GET, PUT, DELETE) | Consulta, actualización o eliminación exitosa |
| `201 Created` | Recurso creado exitosamente | POST de nuevo reporte, categoría, voto |
| `400 Bad Request` | Request malformado | JSON inválido, parámetros faltantes |
| `401 Unauthorized` | Token no válido o ausente | Token expirado, inválido o ausente en ruta protegida |
| `403 Forbidden` | Usuario no autorizado | Intenta modificar/eliminar recurso que no es suyo |
| `404 Not Found` | Recurso no encontrado | ID de recurso inexistente |
| `409 Conflict` | Violación de constraint unique | Voto duplicado, email duplicado |
| `422 Unprocessable Entity` | Datos no válidos | Usuario fuera de 500m para votar, validación de campos |
| `500 Internal Server Error` | Error del servidor | Error no manejado |

---

## 🔄 Flujos Comunes

### Crear Reporte y Votarlo

```bash
# 1. Crear reporte
POST /reports
Body: { category_id: 1, latitude: 13.495, longitude: -88.180, description: "..." }
Response: { report: { id: 25, ... } }

# 2. Votar el reporte (como otro usuario o mismo)
POST /reports/25/votes
Body: { type: "confirm", latitude: 13.495, longitude: -88.180 }
Response: { data: { votes_confirm: 1, votes_resolve: 0, ... } }

# 3. Consultar reporte actualizado
GET /reports/25
Response: { report: { votes: { confirm: 1, resolve: 0 }, user_vote: "confirm", ... } }
```

---

## 📌 Notas Importantes

- **Autenticación opcional en GET /reports/{id}:** El endpoint retorna `user_vote` y `user_voted_at` si se envía Bearer token, pero funciona sin él
- **Validación de distancia:** La validación Haversine es exacta a nivel de metros (máximo 500m)
- **Sincronización de votos:** El cliente debe refrescar el reporte después de votar para obtener conteos actualizados
- **Ventana de edición:** El cliente puede usar `user_voted_at` para implementar ventana de edición de 5 minutos
- **Timestamps ISO 8601:** Todos los timestamps están en formato ISO 8601 con timezone UTC

---

## 📌 RF-06: Retiro de reporte propio

### Ruta: [DELETE] /reports/{report}

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | DELETE                          |
| Auth        | Bearer Token (owner)            |
| Body        | N/A                              |
| Respuesta   | `{ success: bool, message: string }` |
| Estado      | [x] implementada                |
| Notas       | Ruta ya existía (destroy()); se agregó validación: 403 si `created_at` > 5min de antigüedad, o si `votes_confirm+votes_resolve >= 3`. Borra foto asociada y el registro. |

---

## 🛠️ Desarrollo

**Última actualización del código:** 2026-06-11

**Commits recientes:**
- (pendiente commit) - feat: endpoints de "Mi Perfil" — PUT /me, POST /me/avatar, GET /me/reports, GET /me/votes
- `1510cc5` - fix: autenticación opcional en GET /reports/{id}
- `318d0cb` - feat: sistema de votos completado para cliente Android
- `6e89121` - feat: creacion del reporte y endpoints de doc

**TODOs pendientes:**
- [x] Automatizar transiciones de estado basadas en votos ✅ (2026-06-11, RF-11/12/30)
- [x] Sistema de scoring (puntos por reportes verificados) ✅ (2026-06-11, RF-27/29)
- [ ] FCM Push Notifications (nuevo reporte cerca, cambio de estado)
- [x] RF-13/RF-18: comando programado (`reports:archive-stale`, cada 5 min) para auto-archivar reportes ✅ (2026-06-12, ver `docu/db/db.md`)
- [ ] Tests de feature completos (incluir endpoints de Mi Perfil, votos y scoring)
- [ ] Rate limiting en /login y /auth/google

---

**Endpoint de documentación automática:** `GET /api/docs` (retorna JSON con estructura de endpoints)
