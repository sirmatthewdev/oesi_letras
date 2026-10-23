<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error del servidor — OESI · Letras UNMSM</title>
    {{-- Página autocontenida (sin dependencias): se muestra aunque la app falle. --}}
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
            min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;
            color:#fff;background:#143B63;position:relative;overflow:hidden;padding:2rem}
        body::before{content:"";position:absolute;inset:0;opacity:.06;
            background-image:radial-gradient(circle at 1px 1px,#fff 1.5px,transparent 0);background-size:34px 34px}
        body::after{content:"";position:absolute;top:-6rem;right:-6rem;width:24rem;height:24rem;border-radius:50%;
            background:rgba(182,163,80,.2);filter:blur(60px)}
        .wrap{position:relative;z-index:1;max-width:34rem}
        .code{font-family:Georgia,'Times New Roman',serif;font-weight:700;font-size:clamp(4.5rem,18vw,9rem);
            line-height:1;color:#C9AA36;margin-bottom:.25rem}
        .kicker{color:#C9AA36;font-weight:700;letter-spacing:.15em;text-transform:uppercase;font-size:.8rem;margin-bottom:.75rem}
        h1{font-family:Georgia,'Times New Roman',serif;font-size:clamp(1.6rem,5vw,2.2rem);margin-bottom:1rem}
        p{color:rgba(255,255,255,.85);line-height:1.6;margin-bottom:2rem}
        a{display:inline-block;background:#C9AA36;color:#143B63;font-weight:700;text-decoration:none;
            padding:.85rem 1.75rem;border-radius:.5rem;transition:background .2s}
        a:hover{background:#fff}
    </style>
</head>
<body>
    <div class="wrap">
        <p class="code">500</p>
        <p class="kicker">Universidad Nacional Mayor de San Marcos</p>
        <h1>Algo salió mal</h1>
        <p>Ocurrió un error inesperado en el servidor. Estamos trabajando para resolverlo;
            por favor intenta nuevamente en unos minutos.</p>
        <a href="/">Volver al inicio</a>
    </div>
</body>
</html>
