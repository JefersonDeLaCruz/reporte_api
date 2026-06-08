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
