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
