<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Poner pin — Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
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
        .sub { color: var(--muted); margin-bottom: 20px; }
        .alert { padding: 10px 14px; border-radius: var(--radius); margin-bottom: 16px; }
        .alert.ok { background: var(--green-dim); color: var(--green); border: 1px solid rgba(52,211,153,.25); }
        .alert.err { background: #2e1414; color: var(--red); border: 1px solid rgba(248,113,113,.25); }
        .alert.err ul { margin-left: 18px; }
        .layout { display: grid; grid-template-columns: 1fr 340px; gap: 16px; }
        @media (max-width: 800px) { .layout { grid-template-columns: 1fr; } }
        #map { height: 520px; border-radius: var(--radius); border: 1px solid var(--border); }
        @media (max-width: 800px) { #map { height: 380px; } }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; display: flex; flex-direction: column; gap: 12px; }
        label { display: flex; flex-direction: column; gap: 4px; color: var(--muted); font-size: 13px; }
        input, select, textarea { background: var(--bg); color: var(--text); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font: inherit; width: 100%; }
        input:focus, select:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: -1px; }
        textarea { min-height: 90px; resize: vertical; }
        .coords { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .hint { color: var(--muted); font-size: 12px; }
        button { background: var(--accent); color: #fff; border: 0; border-radius: 8px; padding: 10px 14px; font: inherit; font-weight: 600; cursor: pointer; }
        button:hover { filter: brightness(1.1); }
    </style>
</head>
<body>
<div class="container">
    @include('admin._nav')
    <h1>Poner pin</h1>
    <p class="sub">Haz clic en el mapa donde quieras el reporte (o escribe las coordenadas). Se crea en estado <strong>pendiente</strong>, igual que desde la app.</p>

    @if (session('status'))
        <div class="alert ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('admin.reports.store') }}" enctype="multipart/form-data" class="layout">
        @csrf
        <div id="map"></div>

        <div class="panel">
            <div class="coords">
                <label>Latitud
                    <input type="number" step="any" min="-90" max="90" name="latitude" id="lat" value="{{ old('latitude') }}" required>
                </label>
                <label>Longitud
                    <input type="number" step="any" min="-180" max="180" name="longitude" id="lng" value="{{ old('longitude') }}" required>
                </label>
            </div>

            <label>Usuario (autor del reporte)
                <select name="user_id" required>
                    <option value="">Elegir usuario…</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                    @endforeach
                </select>
            </label>

            <label>Categoría
                <select name="category_id" required>
                    <option value="">Elegir categoría…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>

            <label>Descripción
                <textarea name="description" maxlength="500" required>{{ old('description') }}</textarea>
            </label>

            <label>Foto (opcional, máx. 5 MB)
                <input type="file" name="photo" accept="image/*">
            </label>

            <button type="submit">Crear pin</button>
            <span class="hint">Se envía la notificación push a los usuarios, como un reporte normal.</span>
        </div>
    </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    (function () {
        var latInput = document.getElementById('lat');
        var lngInput = document.getElementById('lng');
        var last = @json(session('last'));
        // Centro por defecto: San Miguel, El Salvador
        var center = last ? [last.lat, last.lng] : [13.4833, -88.1833];

        var map = L.map('map').setView(center, last ? 16 : 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = null;
        function place(lat, lng, pan) {
            if (marker) { marker.setLatLng([lat, lng]); }
            else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function () {
                    var p = marker.getLatLng();
                    setInputs(p.lat, p.lng);
                });
            }
            if (pan) { map.panTo([lat, lng]); }
        }
        function setInputs(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
        }

        map.on('click', function (e) {
            setInputs(e.latlng.lat, e.latlng.lng);
            place(e.latlng.lat, e.latlng.lng, false);
        });

        function fromInputs() {
            var lat = parseFloat(latInput.value), lng = parseFloat(lngInput.value);
            if (isFinite(lat) && isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180) {
                place(lat, lng, true);
            }
        }
        latInput.addEventListener('change', fromInputs);
        lngInput.addEventListener('change', fromInputs);
        fromInputs(); // repone el pin tras un error de validación
    })();
</script>
</body>
</html>
