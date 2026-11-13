<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Usuarios — Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #0f1117; --surface: #1a1d27; --border: #2a2d3a; --text: #e8eaf0;
            --muted: #7c8099; --accent: #4f7ef8; --green: #34d399; --green-dim: #0d2e21;
            --red: #f87171; --radius: 10px; --font: system-ui, -apple-system, sans-serif;
        }
        body { background: var(--bg); color: var(--text); font-family: var(--font); font-size: 14px; line-height: 1.5; padding: 32px 16px 60px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        .sub { color: var(--muted); margin-bottom: 24px; }
        .alert { padding: 10px 14px; border-radius: var(--radius); margin-bottom: 16px; }
        .alert.ok { background: var(--green-dim); color: var(--green); border: 1px solid rgba(52,211,153,.25); }
        .alert.err { background: #2e1414; color: var(--red); border: 1px solid rgba(248,113,113,.25); }
        form.search { display: flex; gap: 8px; margin-bottom: 16px; }
        input { background: var(--surface); color: var(--text); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font: inherit; }
        input:focus { outline: 2px solid var(--accent); outline-offset: -1px; }
        form.search input { flex: 1; min-width: 0; }
        button { background: var(--accent); color: #fff; border: 0; border-radius: 8px; padding: 8px 14px; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { filter: brightness(1.1); }
        .table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; }
        th { color: var(--muted); font-weight: 500; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        tr:last-child td { border-bottom: 0; }
        td.num { font-variant-numeric: tabular-nums; text-align: right; }
        th.num { text-align: right; }
        .email { color: var(--muted); font-size: 13px; }
        .level { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #1e2d5a; color: #a9c0ff; font-size: 12px; }
        form.points { display: flex; gap: 6px; }
        form.points input { width: 90px; }
        .pager { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; color: var(--muted); }
        .pager a { color: var(--accent); text-decoration: none; }
        .empty { padding: 24px; text-align: center; color: var(--muted); }
    </style>
</head>
<body>
<div class="container">
    @include('admin._nav')
    <h1>Usuarios</h1>
    <p class="sub">Sumar puntos sube <strong>score</strong> (nivel) y <strong>coins</strong> (saldo de la tienda), igual que los premios por votos.</p>

    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form class="search" method="GET" action="{{ route('admin.users.index') }}">
        <input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nombre o correo" aria-label="Buscar">
        <button type="submit">Buscar</button>
    </form>

    <div class="table-wrap">
        @if ($users->isEmpty())
            <div class="empty">No hay usuarios{{ $search !== '' ? ' que coincidan con la búsqueda' : '' }}.</div>
        @else
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nivel</th>
                    <th class="num">Score</th>
                    <th class="num">Coins</th>
                    <th>Sumar puntos</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>
                            <div>{{ $user->name }}</div>
                            <div class="email">{{ $user->email }}</div>
                        </td>
                        <td><span class="level">{{ $user->level }}</span></td>
                        <td class="num">{{ $user->score }}</td>
                        <td class="num">{{ $user->coins }}</td>
                        <td>
                            <form class="points" method="POST" action="{{ route('admin.users.points', $user) }}">
                                @csrf
                                <input type="number" name="points" min="1" max="10000" step="1" required placeholder="+pts" aria-label="Puntos para {{ $user->name }}">
                                <button type="submit">Sumar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if ($users->hasPages())
        <div class="pager">
            <span>@if ($users->previousPageUrl())<a href="{{ $users->previousPageUrl() }}">← Anterior</a>@endif</span>
            <span>Página {{ $users->currentPage() }} de {{ $users->lastPage() }} · {{ $users->total() }} usuarios</span>
            <span>@if ($users->nextPageUrl())<a href="{{ $users->nextPageUrl() }}">Siguiente →</a>@endif</span>
        </div>
    @endif
</div>
</body>
</html>
