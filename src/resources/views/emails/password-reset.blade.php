<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; }
        h2 { color: #222; }
        p { color: #555; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 16px; padding: 12px 24px; background: #1a73e8; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .note { margin-top: 24px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Recuperar contraseña</h2>
        <p>Recibimos una solicitud para restablecer tu contraseña.</p>
        <p>Copia el siguiente código en la app para continuar:</p>
        <p style="font-size: 28px; font-weight: bold; letter-spacing: 4px; color: #1a73e8;">{{ $token }}</p>
        <p class="note">Este código expira en 60 minutos. Si no solicitaste este cambio, ignora este correo.</p>
    </div>
</body>
</html>
