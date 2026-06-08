# API Documentation

## [2026-06-06] AuthController — rutas de autenticación

### Archivos tocados
- `src/app/Http/Controllers/API/AuthController.php` — implementado register, login, getAllUser, logout
- `src/routes/api.php` — creado con rutas auth públicas y protegidas
- `src/bootstrap/app.php` — registrado api.php en withRouting
- `src/app/Models/User.php` — agregado HasApiTokens trait

### Dependencias
- `laravel/sanctum ^4.3` — instalado vía composer, migration publicada y ejecutada

---

### Ruta: POST /api/register

| Campo     | Detalle                                                    |
|-----------|------------------------------------------------------------|
| Método    | POST                                                       |
| Auth      | No                                                         |
| Body      | `{ name: string, email: string, password: string, password_confirmation: string }` |
| Respuesta | `{ success: bool, message: string, token: string, user: object }` |
| Estado    | [x] lista                                                  |

---

### Ruta: POST /api/login

| Campo     | Detalle                                                    |
|-----------|------------------------------------------------------------|
| Método    | POST                                                       |
| Auth      | No                                                         |
| Body      | `{ email: string, password: string }`                      |
| Respuesta | `{ token: string (Bearer ...), user: object }`             |
| Estado    | [x] lista                                                  |

---

### Ruta: POST /api/logout

| Campo     | Detalle                          |
|-----------|----------------------------------|
| Método    | POST                             |
| Auth      | Bearer Token (auth:sanctum)      |
| Body      | N/A                              |
| Respuesta | `{ success: bool, message: string }` |
| Estado    | [x] lista                        |

---

### Ruta: GET /api/users

| Campo     | Detalle                                           |
|-----------|---------------------------------------------------|
| Método    | GET                                               |
| Auth      | Bearer Token (auth:sanctum)                       |
| Body      | N/A                                               |
| Respuesta | `{ success: bool, users: [{ id, name, email, created_at }] }` |
| Estado    | [x] lista                                         |

---

## [2026-06-06] Google Auth + endpoint /me

### Archivos tocados
- `src/app/Http/Controllers/API/AuthController.php` — agregado googleLogin, me
- `src/routes/api.php` — agregado POST /auth/google, GET /me
- `src/config/services.php` — agregado google.client_id
- `src/.env` / `.env.example` — agregado GOOGLE_CLIENT_ID=

### Dependencias
- `google/apiclient ^2.19` — instalado vía composer

---

### Ruta: POST /api/auth/google

| Campo     | Detalle                                                              |
|-----------|----------------------------------------------------------------------|
| Método    | POST                                                                 |
| Auth      | No                                                                   |
| Body      | `{ id_token: string }` — JWT de Google del cliente móvil            |
| Respuesta | `{ success: bool, token: string (Bearer ...), user: object }`        |
| Estado    | [x] lista                                                            |
| Notas     | Verifica token con Google API. Crea user si no existe, o actualiza google_id/avatar si ya tiene cuenta con ese email |

---

### Ruta: GET /api/me

| Campo     | Detalle                                      |
|-----------|----------------------------------------------|
| Método    | GET                                          |
| Auth      | Bearer Token (auth:sanctum)                  |
| Body      | N/A                                          |
| Respuesta | `{ success: bool, user: object }`            |
| Estado    | [x] lista                                    |

---

### TODOs / Próximos pasos
- [ ] Poner GOOGLE_CLIENT_ID real en .env (obtener desde Google Cloud Console)
- [ ] Agregar rate limiting a /login y /auth/google
- [ ] Proteger /users con policy (solo admin)

## [2026-06-08] Endpoints de categorías, reportes y motor de votos

### Archivos tocados
- `app/Models/Category.php`, `app/Models/Report.php`, `app/Models/ReportVote.php` — modelos + relaciones + Haversine
- `database/migrations/2026_06_08_000003_create_report_votes_table.php` — tabla report_votes (unique report_id+user_id+type)
- `app/Http/Controllers/API/CategoryController.php` — CRUD categorías
- `app/Http/Controllers/API/ReportController.php` — CRUD reportes + foto + ciclo de estados
- `app/Http/Controllers/API/ReportVoteController.php` — motor de votos (radio 500m, bloqueo optimista vía unique constraint)
- `routes/api.php` — rutas registradas
- `database/factories/CategoryFactory.php`, `database/factories/ReportFactory.php` — datos de prueba (puntos San Miguel, El Salvador ~13.4833,-88.1833)
- `database/seeders/CategorySeeder.php`, `database/seeders/ReportSeeder.php` — seeders, llamados desde DatabaseSeeder
- `database/factories/UserFactory.php` — quitado `remember_token` (columna no existe en tabla users, rompía el seed)

### Ruta: [GET] /categories

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | GET                              |
| Auth        | No                              |
| Body        | N/A (query: `active_only=1`)    |
| Respuesta   | `{ success, categories[] }`     |
| Estado      | [x] lista                       |

### Ruta: [GET] /categories/{id}
| Auth | No | Respuesta `{ success, category }` | [x] lista |

### Ruta: [POST] /categories
| Auth | Bearer | Body `{ name, slug, icon, active? }` | Respuesta `{ success, message, category }` | [x] lista |

### Ruta: [PUT] /categories/{id}
| Auth | Bearer | Body `{ name?, slug?, icon?, active? }` | [x] lista |

### Ruta: [DELETE] /categories/{id}
| Auth | Bearer | [x] lista |

### Ruta: [GET] /reports

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | GET                              |
| Auth        | No                              |
| Body        | N/A (query: `status, category_id, per_page`) |
| Respuesta   | `{ success, reports: paginator }` con user y category cargados |
| Estado      | [x] lista                       |

### Ruta: [GET] /reports/{id}
| Auth | No | Respuesta `{ success, report }` | [x] lista |

### Ruta: [POST] /reports

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | POST                             |
| Auth        | Bearer Token                    |
| Body        | `multipart/form-data { category_id, latitude, longitude, description, photo? }` |
| Respuesta   | `{ success, message, report }`  |
| Estado      | [x] lista                       |
| Notas       | foto guardada en disk `public/reports`, status inicial `pending` |

### Ruta: [PUT] /reports/{id}
| Auth | Bearer (solo dueño) | Body `{ category_id?, description?, photo? }` | [x] lista |

### Ruta: [DELETE] /reports/{id}
| Auth | Bearer (solo dueño) | borra foto del disco también | [x] lista |

### Ruta: [PATCH] /reports/{id}/status

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | PATCH                            |
| Auth        | Bearer Token                    |
| Body        | `{ status: pending\|verified\|resolved\|archived }` |
| Respuesta   | `{ success, message, report }`  |
| Estado      | [x] lista (ciclo manual; automatización de 4 estados queda pendiente) |
| Notas       | Cada cambio de estado actualiza `status_changed_at` (timestamp) en el reporte, además del campo específico (`verified_at`/`resolved_at`/`archived_at`) |

### Ruta: [POST] /reports/{id}/votes

| Campo        | Detalle                         |
|-------------|---------------------------------|
| Método      | POST                             |
| Auth        | Bearer Token                    |
| Body        | `{ type: confirm\|resolve, latitude, longitude }` |
| Respuesta   | `{ success, message, report }` o `{ success:false, distance_meters }` si fuera de rango |
| Estado      | [x] lista                       |
| Notas       | Rechaza si distancia (Haversine) > 500m. Bloqueo optimista vía unique(report_id,user_id,type); choque de unicidad → 409 "Ya votaste" |

### Ruta: [DELETE] /reports/{id}/votes/{type}
| Auth | Bearer | retira voto propio (confirm o resolve) y decrementa contador | [x] lista |

### TODOs / Próximos pasos
- [ ] Automatizar ciclo de vida de 4 estados (ej. job que pasa a "resolved" tras N votos_resolve, archiva tras X días)
- [ ] FCM Dispatcher: disparar push cuando reporte cambia de estado o entra nuevo reporte cerca (300m)
- [ ] Motor de scoring: sumar puntos a `users.score` al crear reporte verificado o votar
- [ ] Tests de feature para ReportController y ReportVoteController (incluyendo caso fuera de 500m y voto duplicado)
