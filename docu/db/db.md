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
