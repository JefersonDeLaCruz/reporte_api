# DB Documentation

## [2026-06-06] Tabla users — migración completa

### Archivos tocados
- `src/database/migrations/0001_01_01_000000_create_users_table.php` — campos expandidos según diagrama
- `src/app/Models/User.php` — `$fillable` y `casts` actualizados

### Schema: users

| Columna           | Tipo      | Notas                  |
|-------------------|-----------|------------------------|
| id                | bigint    | PK, autoincrement      |
| name              | string    |                        |
| email             | string    | unique                 |
| password          | string    | hashed                 |
| google_id         | string    | nullable, unique       |
| avatar_url        | string    | nullable               |
| score             | integer   | default 0              |
| level             | string    | default 'beginner'     |
| fcm_token         | string    | nullable               |
| onboarding_done   | boolean   | default false          |
| email_verified_at | timestamp | nullable               |
| created_at        | timestamp | auto (timestamps())    |
| updated_at        | timestamp | auto (timestamps())    |

### TODOs / Próximos pasos
- [ ] Correr `php artisan migrate:fresh` para aplicar cambios
- [ ] Actualizar UserFactory con nuevos campos
- [ ] Definir política de level (enum o string libre)

## [2026-06-08] Tablas categories y reports — creación inicial

### Archivos tocados
- `src/database/migrations/2026_06_08_000001_create_categories_table.php` — tabla categories
- `src/database/migrations/2026_06_08_000002_create_reports_table.php` — tabla reports (FK user_id, category_id)

### Schema: categories

| Columna    | Tipo      | Notas               |
|------------|-----------|---------------------|
| id         | bigint    | PK, autoincrement   |
| name       | string    |                     |
| slug       | string    |                     |
| icon       | string    |                     |
| active     | boolean   | default true        |
| created_at | timestamp | auto (timestamps()) |
| updated_at | timestamp | auto (timestamps()) |

### Schema: reports

| Columna       | Tipo      | Notas                                              |
|---------------|-----------|----------------------------------------------------|
| id            | bigint    | PK, autoincrement                                   |
| user_id       | bigint    | FK -> users.id, cascadeOnDelete                     |
| category_id   | bigint    | FK -> categories.id, cascadeOnDelete                |
| latitude      | decimal(10,7) |                                                 |
| longitude     | decimal(10,7) |                                                 |
| description   | string    |                                                     |
| photo_path    | string    | nullable                                            |
| status        | enum      | pending/verified/resolved/archived, default pending |
| votes_confirm | integer   | default 0                                           |
| votes_resolve | integer   | default 0                                           |
| verified_at   | timestamp | nullable                                            |
| resolved_at   | timestamp | nullable                                            |
| archived_at   | timestamp | nullable                                            |
| created_at    | timestamp | auto (timestamps())                                 |
| updated_at    | timestamp | auto (timestamps())                                 |

Migraciones corridas OK (`php artisan migrate`).

### TODOs / Próximos pasos
- [ ] Crear modelos Category y Report con relaciones (belongsTo user/category, hasMany reports en User/Category)
- [ ] Agregar factories/seeders para categories y reports
- [ ] Definir tabla de votos (relación "genera"/"recibe" pendiente en diagrama, recortada en imagen)

## [2026-06-08] reports — campo status_changed_at

### Archivos tocados
- `src/database/migrations/2026_06_08_000002_create_reports_table.php` — agregada columna `status_changed_at` (timestamp nullable), seteada en cada cambio de estado
- `src/app/Models/Report.php` — `status_changed_at` agregado a Fillable y casts (datetime)
- `src/app/Http/Controllers/API/ReportController.php` — `updateStatus()` ahora setea `status_changed_at = now()` en cada cambio
- `src/database/factories/ReportFactory.php` — states `verified()`/`resolved()` setean `status_changed_at`

### Schema: reports (cambio)

| Columna           | Tipo      | Notas                                              |
|-------------------|-----------|----------------------------------------------------|
| status_changed_at | timestamp | nullable, se actualiza cada vez que cambia status   |

### TODOs / Próximos pasos
- [ ] Correr `php artisan migrate:fresh --seed` para aplicar columna nueva (migración aún no corrida)

## [2026-06-11] users — política de `level` definida (RF-29) + scoring/auto-status (RF-11/12/27/30)

### Archivos tocados
- `src/database/migrations/2026_06_11_000001_update_users_level_values.php` — convierte filas existentes `level='beginner'` → `'nuevo'` y cambia el default de la columna a `'nuevo'`
- `src/app/Models/User.php` — constantes de nivel/umbrales, `addScore(int $points)`, `levelForScore(int $score)`
- `src/app/Models/Report.php` — constantes de umbrales de votos/scoring, `evaluateAutoStatus()` y helpers privados
- `src/app/Http/Controllers/API/ReportVoteController.php` — `store()` invoca `evaluateAutoStatus()` dentro de la transacción y retorna `status` en `data`
- `src/app/Http/Controllers/API/ReportController.php` — `index()`/`show()` agregan `score,level` al select de `user`

### Política de `level` (RF-29)

| Nivel        | Score mínimo | Constante                  |
|--------------|--------------|-----------------------------|
| `nuevo`      | 0            | `User::LEVEL_NUEVO`          |
| `colaborador`| 20           | `User::LEVEL_COLABORADOR`    |
| `guardian`   | 100          | `User::LEVEL_GUARDIAN`       |
| `experto`    | 300          | `User::LEVEL_EXPERTO`        |

> Umbrales elegidos (no estaban en el spec original) — ajustar `User::SCORE_COLABORADOR` / `SCORE_GUARDIAN` / `SCORE_EXPERTO` si se requiere otra progresión. El valor legado `'beginner'` se migra a `'nuevo'`.

### Scoring (RF-27)

| Evento                                              | Beneficiario              | Puntos | Constante                          |
|------------------------------------------------------|---------------------------|--------|-------------------------------------|
| Reporte pasa a `verified`                            | Dueño del reporte          | +10    | `Report::SCORE_OWNER_VERIFIED`      |
| Reporte llega a `verified` o `resolved` (lo primero) | Cada usuario que votó `confirm` | +2 | `Report::SCORE_CONFIRM_MATCH`       |
| Reporte pasa a `resolved`                            | Cada usuario que votó `resolve` | +5 | `Report::SCORE_RESOLVE_MATCH`       |

`User::addScore()` suma el score y recalcula `level` automáticamente con `levelForScore()`.

### Transiciones automáticas de estado (RF-11/RF-12/RF-30)

Evaluadas en `Report::evaluateAutoStatus()`, llamado tras cada voto en `ReportVoteController::store()`:

| Transición            | Condición                                                                 |
|------------------------|----------------------------------------------------------------------------|
| `pending` → `verified` | `votes_confirm >= 5` **o** (`votes_confirm >= 3` y algún votante `confirm` tiene `level = experto`) |
| `pending`/`verified` → `resolved` | `(votes_confirm + votes_resolve) >= 3` y `votes_resolve / total >= 0.7` |

Si un reporte pasa directo de `pending` a `resolved` (sin pasar por `verified`), los votantes `confirm` igual reciben el bono de +2 (controlado vía `verified_at`).

### TODOs / Próximos pasos
- [ ] RF-13: comando programado (scheduler) para auto-archivar reportes `pending`/`verified` sin votos en 24h → `archived`
- [ ] Revisar si los umbrales de `level` (20/100/300) necesitan ajuste según datos reales de uso

## [2026-06-11] Corrección de zona horaria — timestamps a hora salvadoreña

### Archivos tocados
- (datos, vía SQL directo) Se restaron 6 horas a todos los timestamps existentes en: `categories` (created_at, updated_at), `failed_jobs` (failed_at), `password_reset_tokens` (created_at), `personal_access_tokens` (created_at, updated_at, expires_at, last_used_at), `report_votes` (created_at, updated_at), `reports` (created_at, updated_at, archived_at, resolved_at, status_changed_at, verified_at), `users` (created_at, updated_at, email_verified_at)

### Notas
- El servidor MySQL (`api_db`) ya tenía `TZ=America/El_Salvador` correcto (`NOW()` devolvía hora local CST UTC-6).
- El problema: Laravel (`config/app.php`) estaba en `'timezone' => 'UTC'`, por lo que Carbon/`now()` generaba timestamps 6h adelantados respecto a la hora real. Los registros existentes estaban en UTC y se corrigieron restando 6h para alinearlos.
- Cambio de config relacionado en `docu/general/general.md`.

### TODOs / Próximos pasos
- [ ] Verificar que la expiración de `personal_access_tokens.expires_at` siga funcionando bien (la diferencia relativa created_at↔expires_at se preservó, solo cambió el valor absoluto)
- [ ] Revisar si hay código que asuma timestamps en UTC explícitamente (ej. `Carbon::now('UTC')`)
