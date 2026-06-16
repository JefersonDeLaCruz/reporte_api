# Índice General de Documentación

<!-- El agente agrega entradas aquí: -->
<!-- - [YYYY-MM-DD] [modulo] — descripción -->
- [2026-06-06] [db] — tabla users expandida con google_id, avatar_url, score, level, fcm_token, onboarding_done
- [2026-06-15] [api] — password recovery: POST /forgot-password y POST /reset-password vía Brevo SMTP
- [2026-06-06] [api] — AuthController implementado: register, login, logout, getAllUser con Sanctum
- [2026-06-08] [db] — tablas categories, reports y report_votes creadas y migradas
- [2026-06-08] [api] — endpoints CRUD de categorías/reportes + motor de votos (radio 500m, bloqueo optimista) + seeders con datos de prueba en San Miguel, El Salvador
- [2026-06-08] [db][api] — reports: agregada columna status_changed_at, actualizada en cada cambio de estado vía PATCH /reports/{id}/status
- [2026-06-08] [api] — sistema de votos completado: POST /reports/{id}/votes retorna `data` con conteos, GET /reports/{id} incluye `user_vote` y `user_voted_at` para cliente Android
- [2026-06-11] [api] — endpoints de "Mi Perfil": PUT /me (actualizar nombre), POST /me/avatar (subir foto), GET /me/reports y GET /me/votes (paginados)
- [2026-06-11] [db][api] — RF-11/12/27/29/30: transiciones automáticas de estado (verified/resolved) por umbral de votos, sistema de scoring y niveles (nuevo/colaborador/guardian/experto), `user.score`/`level` agregados a /reports y /reports/{id}
- [2026-06-11] [db][general] — corregida zona horaria: `config/app.php` a `America/El_Salvador` y timestamps existentes ajustados (-6h, estaban en UTC)
- [2026-06-12] [api] — sistema real-time con eventos: ReportCreated y ReportStatusChanged implementados, Redis broadcasting configurado, endpoint GET /reports/stream/changes para polling de cambios recientes (cliente Android detecta reportes nuevos sin reload)
- [2026-06-12] [db][api] — RF-13/RF-18: comando `reports:archive-stale` (scheduler cada 5min) verificado end-to-end (resolved >2h y pending/verified >24h → archived)
- [2026-06-13] [api] — RF-06: DELETE /reports/{report} ahora valida 5min de antigüedad y votos<3 antes de permitir retiro (403 si no cumple)
- [2026-06-16] [api] — notificaciones push vía Firebase: POST /me/fcm-token para registrar token, NotificationService envía notificaciones automáticas cuando se crean reportes o cambian estados, credenciales en .firebase/ (gitignored)
