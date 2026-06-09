# Índice General de Documentación

<!-- El agente agrega entradas aquí: -->
<!-- - [YYYY-MM-DD] [modulo] — descripción -->
- [2026-06-06] [db] — tabla users expandida con google_id, avatar_url, score, level, fcm_token, onboarding_done
- [2026-06-06] [api] — AuthController implementado: register, login, logout, getAllUser con Sanctum
- [2026-06-08] [db] — tablas categories, reports y report_votes creadas y migradas
- [2026-06-08] [api] — endpoints CRUD de categorías/reportes + motor de votos (radio 500m, bloqueo optimista) + seeders con datos de prueba en San Miguel, El Salvador
- [2026-06-08] [db][api] — reports: agregada columna status_changed_at, actualizada en cada cambio de estado vía PATCH /reports/{id}/status
- [2026-06-08] [api] — sistema de votos completado: POST /reports/{id}/votes retorna `data` con conteos, GET /reports/{id} incluye `user_vote` y `user_voted_at` para cliente Android
