<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ReporteCiudadano') }} — API</title>
        <style>
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            :root {
                --bg: #0f1117;
                --surface: #1a1d27;
                --border: #2a2d3a;
                --text: #e8eaf0;
                --muted: #7c8099;
                --accent: #4f7ef8;
                --accent-dim: #1e2d5a;
                --green: #34d399;
                --green-dim: #0d2e21;
                --yellow: #fbbf24;
                --red: #f87171;
                --radius: 10px;
                --font: system-ui, -apple-system, sans-serif;
            }
            body {
                background: var(--bg);
                color: var(--text);
                font-family: var(--font);
                font-size: 14px;
                line-height: 1.6;
                min-height: 100vh;
                padding: 40px 20px 60px;
            }
            .container { max-width: 860px; margin: 0 auto; }

            /* Header */
            .header { margin-bottom: 48px; }
            .badge {
                display: inline-flex; align-items: center; gap: 6px;
                background: var(--green-dim); color: var(--green);
                border: 1px solid rgba(52,211,153,.2);
                font-size: 11px; font-weight: 600; letter-spacing: .6px;
                text-transform: uppercase; padding: 4px 10px; border-radius: 100px;
                margin-bottom: 20px;
            }
            .badge::before { content: ''; width: 6px; height: 6px; background: var(--green); border-radius: 50%; }
            h1 { font-size: 32px; font-weight: 700; letter-spacing: -0.5px; margin-bottom: 10px; }
            h1 span { color: var(--accent); }
            .tagline { color: var(--muted); font-size: 15px; max-width: 500px; }

            /* Base URL bar */
            .base-url {
                display: flex; align-items: center; gap: 12px;
                background: var(--surface); border: 1px solid var(--border);
                border-radius: var(--radius); padding: 14px 18px;
                margin-bottom: 40px; font-family: monospace; font-size: 13px;
            }
            .base-url .label { color: var(--muted); font-family: var(--font); font-size: 12px; margin-right: 4px; }
            .base-url .url { color: var(--text); }
            .base-url .sep { color: var(--border); }
            .auth-pill {
                margin-left: auto; display: flex; align-items: center; gap: 6px;
                background: var(--accent-dim); color: var(--accent);
                font-family: var(--font); font-size: 11px; font-weight: 600;
                letter-spacing: .5px; text-transform: uppercase;
                padding: 4px 10px; border-radius: 100px; white-space: nowrap;
            }

            /* Sections */
            h2 { font-size: 11px; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 12px; }
            .section { margin-bottom: 36px; }

            /* Endpoint table */
            .endpoints { display: flex; flex-direction: column; gap: 2px; }
            .endpoint {
                display: grid; grid-template-columns: 64px 1fr auto;
                align-items: center; gap: 14px;
                background: var(--surface); border: 1px solid var(--border);
                border-radius: 8px; padding: 11px 16px;
                transition: border-color .15s;
            }
            .endpoint:hover { border-color: #3a3d4f; }
            .method {
                font-family: monospace; font-size: 11px; font-weight: 700;
                letter-spacing: .5px; text-align: center;
                padding: 3px 8px; border-radius: 5px;
            }
            .method.get    { color: var(--green);  background: var(--green-dim); }
            .method.post   { color: var(--accent);  background: var(--accent-dim); }
            .method.put,
            .method.patch  { color: var(--yellow); background: #2a210a; }
            .method.delete { color: var(--red);    background: #2a0f0f; }
            .path { font-family: monospace; font-size: 13px; color: var(--text); }
            .path .param { color: var(--muted); }
            .desc { color: var(--muted); font-size: 12px; text-align: right; }
            .lock { color: var(--muted); font-size: 12px; }

            /* Info cards */
            .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; }
            .card {
                background: var(--surface); border: 1px solid var(--border);
                border-radius: var(--radius); padding: 18px 20px;
            }
            .card-title { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
            .card p, .card code { font-size: 12px; color: var(--muted); line-height: 1.7; }
            .card code {
                display: inline-block; background: #0f1117;
                border: 1px solid var(--border); border-radius: 4px;
                padding: 2px 7px; font-family: monospace; margin-top: 6px;
            }
            .status-list { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
            .status-chip {
                font-size: 11px; padding: 2px 9px; border-radius: 100px;
                font-weight: 500; letter-spacing: .3px;
            }
            .chip-pending  { background: #2a210a; color: var(--yellow); }
            .chip-verified { background: var(--accent-dim); color: var(--accent); }
            .chip-resolved { background: var(--green-dim); color: var(--green); }
            .chip-archived { background: #1e1e1e; color: var(--muted); }

            /* Docs link */
            .docs-link {
                display: inline-flex; align-items: center; gap: 8px;
                background: var(--accent); color: #fff;
                font-size: 13px; font-weight: 600;
                padding: 10px 20px; border-radius: 8px;
                text-decoration: none; transition: opacity .15s;
            }
            .docs-link:hover { opacity: .85; }
            .footer { margin-top: 48px; padding-top: 24px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: gap; color: var(--muted); font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">

            <div class="header">
                <div class="badge">online</div>
                <h1>{{ config('app.name', 'ReporteCiudadano') }} <span>API</span></h1>
                <p class="tagline">REST API para la plataforma de reportes ciudadanos. Gestión de incidentes, votos comunitarios y notificaciones en tiempo real.</p>
            </div>

            <div class="base-url">
                <span class="label">BASE URL</span>
                <span class="url">{{ config('app.url') }}/api</span>
                <span class="sep">·</span>
                <span class="auth-pill">Bearer Token (Sanctum)</span>
            </div>

            {{-- Auth --}}
            <div class="section">
                <h2>Autenticación</h2>
                <div class="endpoints">
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/register</span>
                        <span class="desc">Registrar usuario</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/login</span>
                        <span class="desc">Iniciar sesión</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/auth/google</span>
                        <span class="desc">Login con Google</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/logout</span>
                        <span class="desc lock">🔒 Cerrar sesión</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/forgot-password</span>
                        <span class="desc">Recuperar contraseña</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/reset-password</span>
                        <span class="desc">Restablecer contraseña</span>
                    </div>
                </div>
            </div>

            {{-- Perfil --}}
            <div class="section">
                <h2>Perfil</h2>
                <div class="endpoints">
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/me</span>
                        <span class="desc lock">🔒 Datos del usuario</span>
                    </div>
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="path">/me</span>
                        <span class="desc lock">🔒 Actualizar nombre</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/me/avatar</span>
                        <span class="desc lock">🔒 Subir avatar</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/me/fcm-token</span>
                        <span class="desc lock">🔒 Registrar FCM token</span>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/me/reports</span>
                        <span class="desc lock">🔒 Mis reportes</span>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/me/votes</span>
                        <span class="desc lock">🔒 Mis votos</span>
                    </div>
                </div>
            </div>

            {{-- Reportes --}}
            <div class="section">
                <h2>Reportes</h2>
                <div class="endpoints">
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/reports</span>
                        <span class="desc">Listar (paginado, filtros)</span>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/reports/<span class="param">{id}</span></span>
                        <span class="desc">Detalle con votos</span>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/reports/stream/changes</span>
                        <span class="desc">Cambios en tiempo real</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/reports</span>
                        <span class="desc lock">🔒 Crear reporte</span>
                    </div>
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="path">/reports/<span class="param">{id}</span></span>
                        <span class="desc lock">🔒 Editar reporte</span>
                    </div>
                    <div class="endpoint">
                        <span class="method patch">PATCH</span>
                        <span class="path">/reports/<span class="param">{id}</span>/status</span>
                        <span class="desc lock">🔒 Cambiar estado</span>
                    </div>
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="path">/reports/<span class="param">{id}</span></span>
                        <span class="desc lock">🔒 Eliminar reporte</span>
                    </div>
                </div>
            </div>

            {{-- Votos --}}
            <div class="section">
                <h2>Votos</h2>
                <div class="endpoints">
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/reports/<span class="param">{id}</span>/votes</span>
                        <span class="desc lock">🔒 Votar reporte</span>
                    </div>
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="path">/reports/<span class="param">{id}</span>/votes/<span class="param">{type}</span></span>
                        <span class="desc lock">🔒 Retirar voto</span>
                    </div>
                </div>
            </div>

            {{-- Categorías --}}
            <div class="section">
                <h2>Categorías</h2>
                <div class="endpoints">
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/categories</span>
                        <span class="desc">Listar categorías</span>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/categories/<span class="param">{id}</span></span>
                        <span class="desc">Detalle</span>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/categories</span>
                        <span class="desc lock">🔒 Crear</span>
                    </div>
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="path">/categories/<span class="param">{id}</span></span>
                        <span class="desc lock">🔒 Actualizar</span>
                    </div>
                    <div class="endpoint">
                        <span class="method delete">DELETE</span>
                        <span class="path">/categories/<span class="param">{id}</span></span>
                        <span class="desc lock">🔒 Eliminar</span>
                    </div>
                </div>
            </div>

            {{-- Info cards --}}
            <div class="section">
                <h2>Referencia rápida</h2>
                <div class="cards">
                    <div class="card">
                        <div class="card-title">Autenticación</div>
                        <p>Enviar token en el header de cada request protegido:</p>
                        <code>Authorization: Bearer {token}</code>
                    </div>
                    <div class="card">
                        <div class="card-title">Ciclo de vida de un reporte</div>
                        <div class="status-list">
                            <span class="status-chip chip-pending">pending</span>
                            <span style="color:var(--muted);align-self:center;">→</span>
                            <span class="status-chip chip-verified">verified</span>
                            <span style="color:var(--muted);align-self:center;">→</span>
                            <span class="status-chip chip-resolved">resolved</span>
                            <span style="color:var(--muted);align-self:center;">→</span>
                            <span class="status-chip chip-archived">archived</span>
                        </div>
                        <p style="margin-top:10px;">Transiciones automáticas por votos (RF-11/12/30).</p>
                    </div>
                    <div class="card">
                        <div class="card-title">Tipos de voto</div>
                        <p><strong style="color:var(--text);">confirm</strong> — Confirmar que el problema existe.<br>
                        <strong style="color:var(--text);">resolve</strong> — Confirmar que fue resuelto.</p>
                        <p style="margin-top:8px;">Radio máximo: 500 m del reporte.</p>
                    </div>
                    <div class="card">
                        <div class="card-title">Documentación JSON</div>
                        <p>Esquema completo de endpoints disponible en:</p>
                        <code>GET /api/docs</code>
                    </div>
                </div>
            </div>

            <div class="footer">
                <span>{{ config('app.name') }} · Laravel v{{ app()->version() }} · PHP {{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}</span>
                <a href="/api/docs" class="docs-link">
                    Ver docs JSON
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 7H11M11 7L8 4M11 7L8 10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>

        </div>
    </body>
</html>
