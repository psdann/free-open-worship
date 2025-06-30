<?php
session_start();

// Función para sanitizar nombres de archivo
function sanitizar_nombre($nombre) {
    $nombre = strtolower($nombre);
    $nombre = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre);
    $nombre = preg_replace('/[^a-z0-9]+/', '_', $nombre);
    $nombre = trim($nombre, '_');
    return $nombre;
}

// Verificar autenticación
function verificar_auth() {
    return isset($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;
}

// Crear directorios si no existen
$directorios = ['letras', 'imagenes', 'videos', 'biblias'];
foreach ($directorios as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Procesar autenticación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clave_seguridad'])) {
    if ($_POST['clave_seguridad'] === 'ENDIOSCONFIAMOS') {
        $_SESSION['autenticado'] = true;
        header("Location: ".$_SERVER['PHP_SELF']."?msg=autenticado");
        exit;
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=clave_incorrecta");
        exit;
    }
}

// Cerrar sesión
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']."?msg=sesion_cerrada");
    exit;
}

// Guardar letra nueva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['letra_titulo'], $_POST['letra_texto'])) {
    if (!verificar_auth()) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=no_autorizado");
        exit;
    }
    
    $titulo = trim($_POST['letra_titulo']);
    $texto = trim($_POST['letra_texto']);
    $lineas = preg_split('/\r\n|\r|\n/', $texto);
    $parrafos = [];
    $tmp = [];
    foreach ($lineas as $linea) {
        if (trim($linea) === '') {
            if (count($tmp) > 0) {
                $parrafos[] = implode("\n", $tmp);
                $tmp = [];
            }
            continue;
        }
        $tmp[] = $linea;
    }
    if (count($tmp) > 0) {
        $parrafos[] = implode("\n", $tmp);
    }
    
    $json = [
        "titulo" => $titulo,
        "parrafos" => $parrafos
    ];
    $nombre_archivo = sanitizar_nombre($titulo) . ".json";
    file_put_contents("letras/$nombre_archivo", json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: ".$_SERVER['PHP_SELF']."?msg=cancion_guardada");
    exit;
}

// Editar canción existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_nombre_archivo'], $_POST['editar_titulo'], $_POST['editar_texto'])) {
    if (!verificar_auth()) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=no_autorizado");
        exit;
    }
    
    $nombre_archivo = basename($_POST['editar_nombre_archivo']);
  if (substr($nombre_archivo, -5) !== '.json') {
    $nombre_archivo .= '.json';
}
    
    $titulo = trim($_POST['editar_titulo']);
    $texto = trim($_POST['editar_texto']);
    $lineas = preg_split('/\r\n|\r|\n/', $texto);
    $parrafos = [];
    $tmp = [];
    foreach ($lineas as $linea) {
        if (trim($linea) === '') {
            if (count($tmp) > 0) {
                $parrafos[] = implode("\n", $tmp);
                $tmp = [];
            }
            continue;
        }
        $tmp[] = $linea;
    }
    if (count($tmp) > 0) {
        $parrafos[] = implode("\n", $tmp);
    }
    
    $json = [
        "titulo" => $titulo,
        "parrafos" => $parrafos
    ];
    file_put_contents("letras/$nombre_archivo", json_encode($json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header("Location: ".$_SERVER['PHP_SELF']."?msg=cancion_editada");
    exit;
}

// Eliminar archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_archivo'], $_POST['eliminar_tipo'])) {
    if (!verificar_auth()) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=no_autorizado");
        exit;
    }
    
    $archivo = basename($_POST['eliminar_archivo']);
    $tipo = $_POST['eliminar_tipo'];
    
    $ruta_completa = '';
    switch($tipo) {
        case 'letra':
            $ruta_completa = "letras/$archivo.json";
            break;
        case 'imagen':
            $ruta_completa = "imagenes/$archivo";
            break;
        case 'video':
            $ruta_completa = "videos/$archivo";
            break;
    }
    
    if (file_exists($ruta_completa) && unlink($ruta_completa)) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=archivo_eliminado");
        exit;
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=error_eliminar");
        exit;
    }
}

// Subida de archivos
if (isset($_FILES['archivo'])) {
    if (!verificar_auth()) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=no_autorizado");
        exit;
    }
    
    $carpeta = $_POST['tipo'] === 'imagen' ? 'imagenes/' : 'videos/';
    $nombre = sanitizar_nombre(pathinfo($_FILES['archivo']['name'], PATHINFO_FILENAME));
    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    $destino = $carpeta . $nombre . '.' . $ext;
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=archivo_subido");
        exit;
    } else {
        header("Location: ".$_SERVER['PHP_SELF']."?msg=error_subida");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="https://freeopenworship.com/freeopenworship-ico.png">
<link rel="apple-touch-icon-precomposed" href="https://freeopenworship.com/freeopenworship-ico.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="https://freeopenworship.com/freeopenworship-ico.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="https://freeopenworship.com/freeopenworship-ico.png">
    <!-- SEO Meta Tags -->
    <title>Free Open Worship - Alternativa Gratuita a EasyWorship | Software de Presentación para Iglesias</title>
    <meta name="description" content="Free Open Worship: Software gratuito y libre para presentaciones de iglesia. Alternativa perfecta a EasyWorship. Proyecta letras, versículos bíblicos, imágenes y videos sin costo. Funciona sin internet.">
    <meta name="keywords" content="alternativa easyworship, software iglesia gratis, presentacion iglesia, proyeccion letras, software adoracion, free easyworship, open source church, presentacion culto, software libre iglesia, proyector iglesia">
    <meta name="author" content="Daniel Quinde">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Spanish">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://freeopenworship.com"/>
    <meta property="og:title" content="Free Open Worship - Alternativa Gratuita a EasyWorship">
    <meta property="og:description" content="Software gratuito para presentaciones de iglesia. Proyecta letras, versículos, imágenes y videos. ¡La mejor alternativa libre a EasyWorship!">
    <meta property="og:image" content="https://freeopenworship.com/Freeopenworship-fondo.png">
    <meta property="og:site_name" content="Free Open Worship">
    <meta property="og:locale" content="es_ES">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://freeopenworship.com/">
    <meta property="twitter:title" content="Free Open Worship - Alternativa Gratuita a EasyWorship">
    <meta property="twitter:description" content="Software gratuito para presentaciones de iglesia. La mejor alternativa libre a EasyWorship. ¡Descárgalo gratis!">
    <meta property="twitter:image" content="https://freeopenworship.com/Freeopenworship-fondo.png">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "Free Open Worship",
        "description": "Software gratuito para presentaciones de iglesia, alternativa perfecta a EasyWorship",
        "applicationCategory": "Multimedia",
        "operatingSystem": "Web Browser, Windows, Mac, Linux",
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        },
        "author": {
            "@type": "Person",
            "name": "Daniel Quinde"
        },
        "url": "https://freeopenworship.com/",
        "downloadUrl": "https://tudominio.com/download",
        "softwareVersion": "1.0",
        "releaseNotes": "Primera versión estable con todas las funciones básicas para presentaciones de iglesia"
    }
    </script>
    
    <!-- Additional SEO -->
    <meta name="theme-color" content="#2c3e50">
    <meta name="msapplication-TileColor" content="#2c3e50">
    <link rel="canonical" href="https://freeopenworship.com/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; }
        .header-title { background: #2c3e50; color: white; padding: 10px 0; }
        .header-menu { background: #34495e; color: white; padding: 8px 0; }
        .main-content { height: 50vh; }
        .programacion { background: #ecf0f1; border-right: 1px solid #bdc3c7; }
        .vista-previa { background: #f8f9fa; border-right: 1px solid #bdc3c7; }
        .live { background: #e8f5e8; }
        .tabs-section { background: white; border-top: 1px solid #bdc3c7; height: 30vh; }
        .footer { background: #2c3e50; color: white; padding: 8px 0; text-align: center; font-size: 0.9em; }
        .item-programacion { 
            background: white; 
            margin: 5px; 
            padding: 10px; 
            border-radius: 5px; 
            cursor: pointer; 
            border-left: 4px solid #3498db;
        }
        .item-programacion:hover { background: #e3f2fd; }
        .item-programacion.selected { background: #bbdefb; border-left-color: #1976d2; }
        .preview-content {
            background: white;
            margin: 10px;
            padding: 15px;
            border-radius: 5px;
            height: calc(100% - 40px);
            overflow-y: auto;
        }
        .live-content { 
            background: #111; 
            color: white; 
            margin: 10px; 
            padding: 15px; 
            border-radius: 5px; 
            height: calc(100% - 40px);
            overflow-y: auto;
            text-align: center;
        }
        .tab-content-custom { height: 200px; overflow-y: auto; }
        .archivo-item { 
            padding: 8px; 
            margin: 2px 0; 
            background: #f8f9fa; 
            border-radius: 3px; 
            cursor: pointer;
            position: relative;
        }
        .archivo-item:hover { background: #e9ecef; }
        .img-thumb-small { max-width: 60px; max-height: 40px; margin-right: 10px; }
        .video-thumb-small { max-width: 60px; max-height: 40px; margin-right: 10px; }
        .letra-parrafo {
            max-height: 120px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .letra-parrafo:hover { background: #e3f2fd; }
        .versiculo-item {
            padding: 5px;
            margin: 2px 0;
            background: #f8f9fa;
            border-radius: 3px;
            cursor: pointer;
            border-left: 3px solid #007bff;
        }
        .versiculo-item:hover { background: #e3f2fd; }
        .btn-eliminar {
            opacity: 0.7;
        }
        .btn-eliminar:hover {
            opacity: 1;
        }
        .botones-archivo {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        .auth-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
    </style>
</head>

<body>
    <?php
    // Mostrar mensajes
    if (isset($_GET['msg'])) {
        if ($_GET['msg'] === 'cancion_guardada') {
            echo "<script>alert('Canción guardada correctamente');</script>";
        }
        if ($_GET['msg'] === 'cancion_editada') {
            echo "<script>alert('Canción editada correctamente');</script>";
        }
        if ($_GET['msg'] === 'archivo_subido') {
            echo "<script>alert('Archivo subido correctamente');</script>";
        }
        if ($_GET['msg'] === 'archivo_eliminado') {
            echo "<script>alert('Archivo eliminado correctamente');</script>";
        }
        if ($_GET['msg'] === 'error_subida') {
            echo "<script>alert('Error al subir archivo');</script>";
        }
        if ($_GET['msg'] === 'error_eliminar') {
            echo "<script>alert('Error al eliminar archivo');</script>";
        }
        if ($_GET['msg'] === 'autenticado') {
            echo "<script>alert('Acceso autorizado');</script>";
        }
        if ($_GET['msg'] === 'clave_incorrecta') {
            echo "<script>alert('Clave incorrecta');</script>";
        }
        if ($_GET['msg'] === 'sesion_cerrada') {
            echo "<script>alert('Sesión cerrada');</script>";
        }
        if ($_GET['msg'] === 'no_autorizado') {
            echo "<script>alert('Debes autenticarte primero');</script>";
        }
    }
    ?>

    <!-- Modal de autenticación -->
    <div class="auth-modal" id="authModal">
        <div class="auth-card">
            <h4><i class="bi bi-shield-lock"></i> Autenticación Requerida</h4>
            <p>Para realizar esta acción, ingresa la clave de seguridad:</p>
            <form id="authForm">
                <div class="mb-3">
                    <input type="password" id="claveInput" class="form-control" placeholder="Clave de seguridad" required>
                </div>
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-unlock"></i> Acceder
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModalAuth()">
                    Cancelar
                </button>
            </form>
        </div>
    </div>

    <!-- Header Title -->
    <div class="header-title">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-music-note-beamed"></i> Free Open Worship</h3>
            <?php if (verificar_auth()): ?>
            <div>
                <span class="badge bg-success me-2"><i class="bi bi-shield-check"></i> Autenticado</span>
                <a href="?logout=1" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header Menu -->
    <div class="header-menu">
        <div class="container-fluid">
            <nav class="navbar navbar-expand-lg navbar-dark p-0">
                <div class="navbar-nav me-auto">
                    <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Home</a>
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-plus-circle"></i> Agregar
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="verificarYEjecutar('abrirModalCancion')">Nueva Canción</a></li>
                            <li><a class="dropdown-item" href="#" onclick="verificarYEjecutar('abrirModalImagen')">Nueva Imagen</a></li>
                            <li><a class="dropdown-item" href="#" onclick="verificarYEjecutar('abrirModalVideo')">Nuevo Video</a></li>
                        </ul>
                    </div>
                    <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#modalAyuda">
    <i class="bi bi-question-circle"></i> Ayuda
</a>
                </div>
                <div class="navbar-nav">
                    <button class="btn btn-success btn-sm" onclick="abrirProyector()">
                        <i class="bi bi-display"></i> Abrir Presentación
                    </button>
                </div>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container-fluid main-content">
        <div class="row h-100">
            <!-- Programación -->
            <div class="col-3 programacion" style="height: calc(100% - 40px); overflow-y: none;">
                <h6 class="p-2 mb-0"><i class="bi bi-list-check"></i> Programación</h6>
                <div id="lista-programacion" style="height: calc(100% - 40px); overflow-y: auto;">
                    <!-- Items de programación se cargan aquí -->
                </div>
            </div>

            <!-- Vista Previa -->
            <div class="col-5 vista-previa" style="height: calc(100% - 40px); overflow-y: none;">
                <h6 class="p-2 mb-0"><i class="bi bi-eye"></i> Vista Previa</h6>
                <div class="preview-content" id="vista-previa-content" style="height: calc(100% - 40px); overflow-y: auto;">
                    <p class="text-muted">Selecciona un elemento de la programación para ver la vista previa</p>
                </div>
            </div>

            <!-- Live -->
            <div class="col-4 live" style="height: calc(100% - 40px); overflow-y: none;">
               <h6 id="liveStatus" class="p-2 mb-0 text-danger">
    <i class="bi bi-broadcast-pin"></i> Live <span class="badge bg-danger">Desconectado</span>
</h6>
                <div class="live-content" id="live-content" style="height: calc(100% - 40px); overflow-y: auto;">
                    <p class="text-muted">Aquí se muestra lo que está en proyección</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Section -->
    <div class="tabs-section">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="biblia-tab" data-bs-toggle="tab" data-bs-target="#biblia" type="button" role="tab">
                    <i class="bi bi-book"></i> Biblias
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="letras-tab" data-bs-toggle="tab" data-bs-target="#letras" type="button" role="tab">
                    <i class="bi bi-music-note"></i> Letras
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="videos-tab" data-bs-toggle="tab" data-bs-target="#videos" type="button" role="tab">
                    <i class="bi bi-play-circle"></i> Videos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="imagenes-tab" data-bs-toggle="tab" data-bs-target="#imagenes" type="button" role="tab">
                    <i class="bi bi-image"></i> Imágenes
                </button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <!-- Biblias -->
            <div class="tab-pane fade show active" id="biblia" role="tabpanel">
                <div class="p-3">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="biblia-select">
                                <option value="">-- Selecciona Biblia --</option>
                                <?php
                                if (is_dir('biblias')) {
                                    $files = glob('biblias/*.json');
                                    foreach ($files as $file) {
                                        $name = basename($file, '.json');
                                        echo "<option value=\"$name\">$name</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input class="form-control form-control-sm" id="libro-input" placeholder="Escribe el libro..." list="libros-lista" autocomplete="off">
                            <datalist id="libros-lista"></datalist>
                        </div>
                        <div class="col-md-2">
                            <input class="form-control form-control-sm" id="capitulo-input" type="number" min="1" value="1" placeholder="Capítulo">
                        </div>
                        <div class="col-md-2">
                            <input class="form-control form-control-sm" id="versiculo-input" type="number" min="1" value="1" placeholder="Versículo">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm" onclick="agregarVersiculoAProgramacion()">
                                <i class="bi bi-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                    <div class="tab-content-custom mt-2" id="versiculos-lista">
                        <p class="text-muted">Selecciona una biblia, libro y capítulo para ver los versículos</p>
                    </div>
                </div>
            </div>

            <!-- Letras -->
            <div class="tab-pane fade" id="letras" role="tabpanel">
                <div class="p-3">
                    <button class="btn btn-success btn-sm mb-2" onclick="verificarYEjecutar('abrirModalCancion')">
                        <i class="bi bi-plus"></i> Nueva Canción
                    </button>
                    <div class="tab-content-custom" id="letras-lista">
                        <?php
                        if (is_dir('letras')) {
                            $files = glob('letras/*.json');
                            foreach ($files as $file) {
                                $name = basename($file, '.json');
                                $json = json_decode(file_get_contents($file), true);
                                $titulo = htmlspecialchars($json['titulo'] ?? $name);
                                echo "<div class='archivo-item' ondblclick='agregarLetraAProgramacion(\"$name\")'>
                                        <div class='botones-archivo'>
                                            <button class='btn btn-sm btn-outline-primary me-1' onclick='event.stopPropagation(); agregarLetraAProgramacion(\"$name\")' title='Agregar'>
                                                <i class='bi bi-plus'></i>
                                            </button>
                                            <button class='btn btn-sm btn-outline-secondary me-1' onclick='event.stopPropagation(); verificarYEjecutar(\"editarCancion\", \"$name\")' title='Editar'>
                                                <i class='bi bi-pencil'></i>
                                            </button>
                                            <button class='btn btn-sm btn-outline-danger' onclick='event.stopPropagation(); verificarYEjecutar(\"eliminarArchivo\", \"$name\", \"letra\", \"$titulo\")' title='Eliminar'>
                                                <i class='bi bi-trash'></i>
                                            </button>
                                        </div>
                                        <div style='margin-left: 120px;'>
                                            <i class='bi bi-music-note'></i> $titulo
                                        </div>
                                      </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Videos -->
            <div class="tab-pane fade" id="videos" role="tabpanel">
                <div class="p-3">
                    <button class="btn btn-success btn-sm mb-2" onclick="verificarYEjecutar('abrirModalVideo')">
                        <i class="bi bi-plus"></i> Nuevo Video
                    </button>
                    <div class="tab-content-custom" id="videos-lista">
                        <?php
                        if (is_dir('videos')) {
                            $vid_exts = ['mp4','webm','ogg','avi','mov'];
                            $videos = [];
                            foreach ($vid_exts as $ext) {
                                foreach (glob("videos/*.$ext") as $vid) {
                                    $videos[] = $vid;
                                }
                            }
                            foreach ($videos as $vid) {
                                $src = htmlspecialchars($vid);
                                $name = basename($vid);
                                echo "<div class='archivo-item' ondblclick='agregarVideoAProgramacion(\"$src\")'>
                                        <div class='botones-archivo'>
                                            <button class='btn btn-sm btn-outline-primary me-1' onclick='event.stopPropagation(); agregarVideoAProgramacion(\"$src\")' title='Agregar'>
                                                <i class='bi bi-plus'></i>
                                            </button>
                                            <button class='btn btn-sm btn-outline-danger' onclick='event.stopPropagation(); verificarYEjecutar(\"eliminarArchivo\", \"$name\", \"video\", \"$name\")' title='Eliminar'>
                                                <i class='bi bi-trash'></i>
                                            </button>
                                        </div>
                                        <div style='margin-left: 80px;'>
                                            <video src='$src' class='video-thumb-small' preload='metadata' muted></video>
                                            $name
                                        </div>
                                      </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Imágenes -->
            <div class="tab-pane fade" id="imagenes" role="tabpanel">
                <div class="p-3">
                    <button class="btn btn-success btn-sm mb-2" onclick="verificarYEjecutar('abrirModalImagen')">
                        <i class="bi bi-plus"></i> Nueva Imagen
                    </button>
                    <div class="tab-content-custom" id="imagenes-lista">
                        <?php
                        if (is_dir('imagenes')) {
                            $img_exts = ['jpg','jpeg','png','gif','webp','bmp'];
                            $imagenes = [];
                            foreach ($img_exts as $ext) {
                                foreach (glob("imagenes/*.$ext") as $img) {
                                    $imagenes[] = $img;
                                }
                            }
                            foreach ($imagenes as $img) {
                                $src = htmlspecialchars($img);
                                $name = basename($img);
                                echo "<div class='archivo-item' ondblclick='agregarImagenAProgramacion(\"$src\")'>
                                        <div class='botones-archivo'>
                                            <button class='btn btn-sm btn-outline-primary me-1' onclick='event.stopPropagation(); agregarImagenAProgramacion(\"$src\")' title='Agregar'>
                                                <i class='bi bi-plus'></i>
                                            </button>
                                            <button class='btn btn-sm btn-outline-danger' onclick='event.stopPropagation(); verificarYEjecutar(\"eliminarArchivo\", \"$name\", \"imagen\", \"$name\")' title='Eliminar'>
                                                <i class='bi bi-trash'></i>
                                            </button>
                                        </div>
                                        <div style='margin-left: 80px;'>
                                            <img src='$src' alt='$name' class='img-thumb-small'>
                                            $name
                                        </div>
                                      </div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer fixed-bottom">
        Software libre para presentación de iglesias desarrollado por Daniel Quinde
    </div>

    <!-- Modales -->
    <!-- Modal Nueva Canción -->
<div class="modal fade" id="modalCancion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Canción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Título de la Canción:</label>
                        <input type="text" name="letra_titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Letra (una línea por verso):</label>
                        <div class="mb-2">
                            <small class="text-muted">Herramientas de formato:</small><br>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('nueva', 2)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 2
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('nueva', 3)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 3
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('nueva', 4)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 4
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFormato('nueva')">
                                <i class="bi bi-eraser"></i> Limpiar formato
                            </button>
                        </div>
                        <textarea name="letra_texto" id="letra_texto_nueva" class="form-control" rows="12" required placeholder="Escribe aquí la letra de la canción..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Canción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Canción -->
<div class="modal fade" id="modalEditarCancion" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Canción</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="editar_nombre_archivo" id="editar_nombre_archivo">
                    <div class="mb-3">
                        <label class="form-label">Título de la Canción:</label>
                        <input type="text" name="editar_titulo" id="editar_titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Letra (una línea por verso):</label>
                        <div class="mb-2">
                            <small class="text-muted">Herramientas de formato:</small><br>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('editar', 2)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 2
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('editar', 3)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 3
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="separarEnParrafos('editar', 4)">
                                <i class="bi bi-paragraph"></i> Separar en párrafos de 4
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFormato('editar')">
                                <i class="bi bi-eraser"></i> Limpiar formato
                            </button>
                        </div>
                        <textarea name="editar_texto" id="editar_texto" class="form-control" rows="12" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Modal Nueva Imagen -->
    <div class="modal fade" id="modalImagen" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Imagen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="imagen">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Imagen:</label>
                            <input type="file" name="archivo" class="form-control" accept="image/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Subir Imagen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Video -->
    <div class="modal fade" id="modalVideo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="tipo" value="video">
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Video:</label>
                            <input type="file" name="archivo" class="form-control" accept="video/*" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Subir Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="modalEliminar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="eliminar_archivo" id="eliminar_archivo">
                        <input type="hidden" name="eliminar_tipo" id="eliminar_tipo">
                        <p>¿Estás seguro de que quieres eliminar permanentemente este archivo?</p>
                        <div class="alert alert-warning">
                            <strong id="nombre_archivo_eliminar"></strong>
                        </div>
                        <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar Permanentemente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables globales
        let proyectorWindow = null;
        let programacion = [];
        let itemSeleccionado = null;
        let datosBiblia = null;
        let bibliaSeleccionada = null;
        let accionPendiente = null;
        let parametrosAccion = [];

        // Sistema de autenticación
        function verificarYEjecutar(funcion, ...parametros) {
            <?php if (verificar_auth()): ?>
                // Si ya está autenticado, ejecutar directamente
                ejecutarFuncion(funcion, parametros);
            <?php else: ?>
                // Si no está autenticado, guardar la acción y mostrar modal
                accionPendiente = funcion;
                parametrosAccion = parametros;
                document.getElementById('authModal').style.display = 'flex';
                document.getElementById('claveInput').focus();
            <?php endif; ?>
        }

        function ejecutarFuncion(funcion, parametros) {
            switch(funcion) {
                case 'abrirModalCancion':
                    abrirModalCancion();
                    break;
                case 'abrirModalImagen':
                    abrirModalImagen();
                    break;
                case 'abrirModalVideo':
                    abrirModalVideo();
                    break;
                case 'editarCancion':
                    editarCancion(parametros[0]);
                    break;
                case 'eliminarArchivo':
                    eliminarArchivo(parametros[0], parametros[1], parametros[2]);
                    break;
            }
        }

        // Manejar formulario de autenticación
        document.getElementById('authForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const clave = document.getElementById('claveInput').value;
            
            if (clave === 'ENDIOSCONFIAMOS') {
                // Enviar autenticación al servidor
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.name = 'clave_seguridad';
                input.value = clave;
                form.appendChild(input);
                
                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Clave incorrecta');
                document.getElementById('claveInput').value = '';
                document.getElementById('claveInput').focus();
            }
        });

        function cerrarModalAuth() {
            document.getElementById('authModal').style.display = 'none';
            document.getElementById('claveInput').value = '';
            accionPendiente = null;
            parametrosAccion = [];
        }

        // Funciones para abrir modales
        function abrirModalCancion() {
            new bootstrap.Modal(document.getElementById('modalCancion')).show();
        }
        function abrirModalImagen() {
            new bootstrap.Modal(document.getElementById('modalImagen')).show();
        }
        function abrirModalVideo() {
            new bootstrap.Modal(document.getElementById('modalVideo')).show();
        }

        // Función para editar canción
        function editarCancion(nombre) {
            fetch(`letras/${nombre}.json`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editar_nombre_archivo').value = nombre;
                    document.getElementById('editar_titulo').value = data.titulo;
                    document.getElementById('editar_texto').value = data.parrafos.join('\n\n');
                    new bootstrap.Modal(document.getElementById('modalEditarCancion')).show();
                })
                .catch(error => {
                    alert('No se pudo cargar la canción para editar');
                });
        }

        // Función para eliminar archivo
        function eliminarArchivo(archivo, tipo, nombre) {
            document.getElementById('eliminar_archivo').value = archivo;
            document.getElementById('eliminar_tipo').value = tipo;
            document.getElementById('nombre_archivo_eliminar').textContent = nombre;
            new bootstrap.Modal(document.getElementById('modalEliminar')).show();
        }
function abrirProyector() {
    proyectorWindow = window.open(
        'proyectar.html',
        'proyector',
        'width=900,height=700,menubar=no,toolbar=no,location=no,status=no,resizable=yes'
    );
    if (proyectorWindow) {
        proyectorWindow.focus();
        actualizarEstadoLive(true); // Cambia a verde
        
        // Detectar cuando se cierre la ventana del proyector
        proyectorWindow.addEventListener('beforeunload', function() {
            actualizarEstadoLive(false);
        });
        
    } else {
        alert('Por favor, permite los popups para este sitio.');
        actualizarEstadoLive(false); // Cambia a rojo
    }
}

// Función para actualizar el estado visual del Live
function actualizarEstadoLive(conectado) {
    const liveStatus = document.getElementById('liveStatus');
    if (conectado) {
        liveStatus.classList.remove('text-danger');
        liveStatus.classList.add('text-success');
        liveStatus.innerHTML = '<i class="bi bi-broadcast"></i> Live <span class="badge bg-success">Conectado</span>';
    } else {
        liveStatus.classList.remove('text-success');
        liveStatus.classList.add('text-danger');
        liveStatus.innerHTML = '<i class="bi bi-broadcast-pin"></i> Live <span class="badge bg-danger">Desconectado</span>';
    }
}

// Verificar estado de la ventana cada segundo
setInterval(function() {
    if (proyectorWindow && !proyectorWindow.closed) {
        actualizarEstadoLive(true);
    } else {
        if (proyectorWindow) { // Solo cambiar a rojo si había una ventana abierta
            actualizarEstadoLive(false);
            proyectorWindow = null;
        }
    }
}, 1000);

        // Event listeners para las biblias
        document.addEventListener('DOMContentLoaded', function() {
            // Cargar biblia seleccionada
            document.getElementById('biblia-select').addEventListener('change', function() {
                const nombre = this.value;
                if (!nombre) {
                    document.getElementById('libros-lista').innerHTML = '';
                    document.getElementById('libro-input').value = '';
                    document.getElementById('capitulo-input').value = '1';
                    document.getElementById('versiculo-input').value = '1';
                    document.getElementById('versiculos-lista').innerHTML = '<p class="text-muted">Selecciona una biblia</p>';
                    return;
                }
                
                fetch(`biblias/${nombre}.json`)
                    .then(res => {
                        if (!res.ok) throw new Error('No se pudo cargar la biblia');
                        return res.json();
                    })
                    .then(data => {
                        datosBiblia = data;
                        bibliaSeleccionada = nombre;
                        
                        // Detectar formato y cargar libros
                        if (data.verses) {
                            cargarLibrosVerses();
                        } else if (data.libros) {
                            cargarLibrosLibros();
                        } else {
                            throw new Error('Formato de biblia no reconocido');
                        }
                    })
                    .catch(error => {
                        console.error('Error cargando biblia:', error);
                        alert('Error al cargar la biblia. Verifica que el archivo existe y es válido.');
                    });
            });

            // Event listener para el input de libro
            document.getElementById('libro-input').addEventListener('input', function() {
                actualizarCapitulosYVersiculos();
            });

            // Event listener para el input de capítulo
            document.getElementById('capitulo-input').addEventListener('input', function() {
                actualizarVersiculos();
            });
        });

        // Funciones para formato "verses"
        function cargarLibrosVerses() {
            const datalist = document.getElementById('libros-lista');
            datalist.innerHTML = '';
            
            const libros = {};
            datosBiblia.verses.forEach(v => {
                libros[v.book] = v.book_name;
            });
            
            Object.keys(libros).sort((a,b) => parseInt(a) - parseInt(b)).forEach(num => {
                const option = document.createElement('option');
                option.value = libros[num];
                option.dataset.bookId = num;
                datalist.appendChild(option);
            });
        }

        function actualizarCapitulosYVersiculos() {
            const libroInput = document.getElementById('libro-input');
            const datalist = document.getElementById('libros-lista');
            const option = Array.from(datalist.options).find(opt => 
                opt.value.toLowerCase() === libroInput.value.toLowerCase()
            );
            
            if (option && datosBiblia && datosBiblia.verses) {
                const bookId = parseInt(option.dataset.bookId);
                
                // Calcular el máximo de capítulos
                const capitulos = new Set();
                datosBiblia.verses.forEach(v => {
                    if (v.book === bookId) capitulos.add(v.chapter);
                });
                const maxCap = Math.max(...capitulos);
                
                const capInput = document.getElementById('capitulo-input');
                capInput.max = maxCap;
                if (parseInt(capInput.value) > maxCap) {
                    capInput.value = 1;
                }
                
                actualizarVersiculos();
            }
        }

        function actualizarVersiculos() {
            const libroInput = document.getElementById('libro-input');
            const datalist = document.getElementById('libros-lista');
            const option = Array.from(datalist.options).find(opt => 
                opt.value.toLowerCase() === libroInput.value.toLowerCase()
            );
            
            if (option && datosBiblia && datosBiblia.verses) {
                const bookId = parseInt(option.dataset.bookId);
                const capNum = parseInt(document.getElementById('capitulo-input').value);
                
                const versiculos = datosBiblia.verses.filter(v => 
                    v.book === bookId && v.chapter === capNum
                );
                
                if (versiculos.length > 0) {
                    const maxVers = Math.max(...versiculos.map(v => v.verse));
                    const versInput = document.getElementById('versiculo-input');
                    versInput.max = maxVers;
                    if (parseInt(versInput.value) > maxVers) {
                        versInput.value = 1;
                    }
                }
            }
        }

        // Funciones para formato "libros"
        function cargarLibrosLibros() {
            const datalist = document.getElementById('libros-lista');
            datalist.innerHTML = '';
            
            datosBiblia.libros.forEach((libro, idx) => {
                const option = document.createElement('option');
                option.value = libro.nombre || `Libro ${idx + 1}`;
                option.dataset.bookIdx = idx;
                datalist.appendChild(option);
            });
        }

        // Función para agregar versículo a programación
        function agregarVersiculoAProgramacion() {
            if (!datosBiblia) {
                alert('Selecciona una biblia primero');
                return;
            }
            
            const libroInput = document.getElementById('libro-input');
            const datalist = document.getElementById('libros-lista');
            const option = Array.from(datalist.options).find(opt => 
                opt.value.toLowerCase() === libroInput.value.toLowerCase()
            );
            
            if (!option) {
                alert('Selecciona un libro válido');
                return;
            }
            
            const capNum = parseInt(document.getElementById('capitulo-input').value);
            const versNum = parseInt(document.getElementById('versiculo-input').value);
            
            if (isNaN(capNum) || isNaN(versNum)) {
                alert('Ingresa capítulo y versículo válidos');
                return;
            }
            
            let item;
            if (datosBiblia.verses) {
                const bookId = parseInt(option.dataset.bookId);
                const versiculosCap = datosBiblia.verses.filter(v => 
                    v.book === bookId && v.chapter === capNum
                );
                const versiculoSel = versiculosCap.find(v => v.verse === versNum);
                if (!versiculoSel) {
                    alert('Versículo no encontrado');
                    return;
                }
                item = {
                    tipo: 'versiculo',
                    titulo: `${versiculoSel.book_name} ${capNum}:${versNum}`,
                    data: {
                        biblia: bibliaSeleccionada,
                        libro: versiculoSel.book_name,
                        capitulo: capNum,
                        versiculos: versiculosCap,
                        versiculoSeleccionado: versNum,
                        formato: 'verses'
                    }
                };
            } else if (datosBiblia.libros) {
                const bookIdx = parseInt(option.dataset.bookIdx);
                const libro = datosBiblia.libros[bookIdx];
                if (!libro.capitulos || !libro.capitulos[capNum - 1] || !libro.capitulos[capNum - 1][versNum - 1]) {
                    alert('Versículo no encontrado');
                    return;
                }
                const versiculosCap = libro.capitulos[capNum - 1];
                item = {
                    tipo: 'versiculo',
                    titulo: `${libro.nombre || `Libro ${bookIdx + 1}`} ${capNum}:${versNum}`,
                    data: {
                        biblia: bibliaSeleccionada,
                        libro: libro.nombre || `Libro ${bookIdx + 1}`,
                        capitulo: capNum,
                        versiculos: versiculosCap,
                        versiculoSeleccionado: versNum,
                        formato: 'libros'
                    }
                };
            }
            programacion.push(item);
            actualizarProgramacion();
        }

        // Funciones para agregar a programación
        function agregarLetraAProgramacion(nombre) {
            fetch(`letras/${nombre}.json`)
                .then(res => res.json())
                .then(data => {
                    const item = {
                        tipo: 'letra',
                        titulo: data.titulo,
                        data: data,
                        archivo: nombre
                    };
                    programacion.push(item);
                    actualizarProgramacion();
                })
                .catch(error => {
                    console.error('Error cargando letra:', error);
                    alert('Error al cargar la letra');
                });
        }

        function agregarImagenAProgramacion(src) {
            const item = {
                tipo: 'imagen',
                titulo: basename(src),
                src: src
            };
            programacion.push(item);
            actualizarProgramacion();
        }

        function agregarVideoAProgramacion(src) {
            const item = {
                tipo: 'video',
                titulo: basename(src),
                src: src
            };
            programacion.push(item);
            actualizarProgramacion();
        }

        // Función para eliminar ítem de programación
        function eliminarItemProgramacion(event, index) {
            event.stopPropagation();
            if (confirm('¿Estás seguro de que quieres eliminar este elemento?')) {
                if (itemSeleccionado === programacion[index]) {
                    itemSeleccionado = null;
                    document.getElementById('vista-previa-content').innerHTML = '<p class="text-muted">Selecciona un elemento de la programación para ver la vista previa</p>';
                    document.getElementById('live-content').innerHTML = '<p class="text-muted">Aquí se muestra lo que está en proyección</p>';
                }
                
                programacion.splice(index, 1);
                actualizarProgramacion();
            }
        }

        function actualizarProgramacion() {
            const lista = document.getElementById('lista-programacion');
            lista.innerHTML = '';
            programacion.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = 'item-programacion d-flex justify-content-between align-items-center';
                div.onclick = () => seleccionarItemProgramacion(index);
                
                let icono = '';
                switch(item.tipo) {
                    case 'letra': icono = 'bi-music-note'; break;
                    case 'imagen': icono = 'bi-image'; break;
                    case 'video': icono = 'bi-play-circle'; break;
                    case 'versiculo': icono = 'bi-book'; break;
                }
                
                div.innerHTML = `
                    <span><i class="bi ${icono}"></i> ${item.titulo}</span>
                    <button class="btn btn-sm btn-danger btn-eliminar" onclick="eliminarItemProgramacion(event, ${index})" title="Eliminar">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
                lista.appendChild(div);
            });
        }

        function seleccionarItemProgramacion(index) {
            document.querySelectorAll('.item-programacion').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('.item-programacion')[index].classList.add('selected');
            itemSeleccionado = programacion[index];
            mostrarVistaPrevia();
        }

        function mostrarVistaPrevia() {
            const preview = document.getElementById('vista-previa-content');
            if (!itemSeleccionado) return;

            switch(itemSeleccionado.tipo) {
                case 'letra':
                    let html = `<h5>${itemSeleccionado.titulo}</h5>`;
                    if (itemSeleccionado.data.parrafos) {
                        itemSeleccionado.data.parrafos.forEach((parrafo, idx) => {
                            html += `<div class="letra-parrafo" onclick="proyectarParrafo(${idx})">
                                       ${parrafo.replace(/\n/g, '<br>')}
                                     </div>`;
                        });
                    }
                    preview.innerHTML = html;
                    break;
                case 'imagen':
                    preview.innerHTML = `<img src="${itemSeleccionado.src}" class="img-fluid" onclick="proyectarImagen('${itemSeleccionado.src}')" style="cursor:pointer;">`;
                    break;
                case 'video':
                    preview.innerHTML = `<video src="${itemSeleccionado.src}" class="w-100" controls muted onclick="proyectarVideo('${itemSeleccionado.src}')" style="cursor:pointer;"></video>`;
                    break;
                case 'versiculo':
                    let htmlVers = `<h5>${itemSeleccionado.data.libro} ${itemSeleccionado.data.capitulo}</h5>`;
                    if (itemSeleccionado.data.versiculos && Array.isArray(itemSeleccionado.data.versiculos)) {
                        if (itemSeleccionado.data.formato === 'verses') {
                            itemSeleccionado.data.versiculos.forEach((v, idx) => {
                                const selected = (v.verse === itemSeleccionado.data.versiculoSeleccionado) ? 'style="background:#bbdefb;"' : '';
                                htmlVers += `<div class="letra-parrafo" ${selected} onclick="proyectarVersiculoObj(${idx})">
                                               <strong>${v.verse}.</strong> ${v.text}
                                             </div>`;
                            });
                        } else {
                            itemSeleccionado.data.versiculos.forEach((versiculo, idx) => {
                                const selected = ((idx + 1) === itemSeleccionado.data.versiculoSeleccionado) ? 'style="background:#bbdefb;"' : '';
                                htmlVers += `<div class="letra-parrafo" ${selected} onclick="proyectarVersiculoArray(${idx})">
                                               <strong>${idx + 1}.</strong> ${versiculo}
                                             </div>`;
                            });
                        }
                    }
                    preview.innerHTML = htmlVers;
                    setTimeout(() => {
                        const parrafos = preview.querySelectorAll('.letra-parrafo');
                        let idx = 0;
                        if (itemSeleccionado.data.formato === 'verses') {
                            idx = itemSeleccionado.data.versiculos.findIndex(v => v.verse === itemSeleccionado.data.versiculoSeleccionado);
                        } else {
                            idx = itemSeleccionado.data.versiculoSeleccionado - 1;
                        }
                        if (parrafos[idx]) parrafos[idx].scrollIntoView({behavior: "smooth", block: "center"});
                    }, 100);
                    break;
            }
        }

        function proyectarParrafo(index) {
            if (proyectorWindow && !proyectorWindow.closed && itemSeleccionado) {
                const parrafo = itemSeleccionado.data.parrafos[index];
                proyectorWindow.postMessage({
                    tipo: 'mostrarLetra',
                    titulo: itemSeleccionado.titulo,
                    texto: parrafo
                }, '*');
                actualizarLive(`<h5>${itemSeleccionado.titulo}</h5><p>${parrafo.replace(/\n/g, '<br>')}</p>`);
            }
        }

        function proyectarVersiculoArray(index) {
            if (proyectorWindow && !proyectorWindow.closed && itemSeleccionado) {
                const versiculo = itemSeleccionado.data.versiculos[index];
                const titulo = `${itemSeleccionado.data.libro} ${itemSeleccionado.data.capitulo}:${index + 1}`;
                proyectorWindow.postMessage({
                    tipo: 'mostrarVersiculo',
                    titulo: titulo,
                    texto: versiculo
                }, '*');
                actualizarLive(`<h5>${titulo}</h5><p>${versiculo}</p>`);
            }
        }

        function proyectarVersiculoObj(index) {
            if (proyectorWindow && !proyectorWindow.closed && itemSeleccionado) {
                const v = itemSeleccionado.data.versiculos[index];
                const titulo = `${itemSeleccionado.data.libro} ${itemSeleccionado.data.capitulo}:${v.verse}`;
                proyectorWindow.postMessage({
                    tipo: 'mostrarVersiculo',
                    titulo: titulo,
                    texto: v.text
                }, '*');
                actualizarLive(`<h5>${titulo}</h5><p>${v.text}</p>`);
            }
        }

        function proyectarImagen(src) {
            if (proyectorWindow && !proyectorWindow.closed) {
                proyectorWindow.postMessage({
                    tipo: 'mostrarImagen',
                    src: src
                }, '*');
                actualizarLive(`<img src="${src}" class="img-fluid">`);
            }
        }

        function proyectarVideo(src) {
            if (proyectorWindow && !proyectorWindow.closed) {
                proyectorWindow.postMessage({
                    tipo: 'mostrarVideo',
                    src: src
                }, '*');
                actualizarLive(`<video src="${src}" class="w-100" muted autoplay></video>`);
            }
        }

        function actualizarLive(contenido) {
            document.getElementById('live-content').innerHTML = contenido;
        }

        function basename(path) {
            return path.split('/').reverse()[0];
        }


        // Funciones para formatear letras de canciones
function separarEnParrafos(tipo, lineasPorParrafo) {
    const textareaId = tipo === 'nueva' ? 'letra_texto_nueva' : 'editar_texto';
    const textarea = document.getElementById(textareaId);
    const texto = textarea.value.trim();
    
    if (!texto) {
        alert('Primero escribe o pega la letra de la canción');
        return;
    }
    
    // Dividir en líneas y limpiar líneas vacías
    const lineas = texto.split(/\r\n|\r|\n/).filter(linea => linea.trim() !== '');
    
    if (lineas.length === 0) {
        alert('No hay contenido para formatear');
        return;
    }
    
    // Agrupar en párrafos
    const parrafos = [];
    for (let i = 0; i < lineas.length; i += lineasPorParrafo) {
        const parrafo = lineas.slice(i, i + lineasPorParrafo);
        parrafos.push(parrafo.join('\n'));
    }
    
    // Unir párrafos con doble salto de línea
    const textoFormateado = parrafos.join('\n\n');
    textarea.value = textoFormateado;
    
    // Mostrar confirmación
    const mensaje = `Texto separado en ${parrafos.length} párrafos de ${lineasPorParrafo} líneas cada uno`;
    mostrarNotificacion(mensaje, 'success');
}

function limpiarFormato(tipo) {
    const textareaId = tipo === 'nueva' ? 'letra_texto_nueva' : 'editar_texto';
    const textarea = document.getElementById(textareaId);
    const texto = textarea.value.trim();
    
    if (!texto) {
        return;
    }
    
    // Limpiar múltiples saltos de línea y espacios extra
    const textoLimpio = texto
        .split(/\r\n|\r|\n/)
        .map(linea => linea.trim())
        .filter(linea => linea !== '')
        .join('\n');
    
    textarea.value = textoLimpio;
    mostrarNotificacion('Formato limpiado - todas las líneas en un solo bloque', 'info');
}

function mostrarNotificacion(mensaje, tipo) {
    // Crear elemento de notificación
    const notificacion = document.createElement('div');
    notificacion.className = `alert alert-${tipo === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
    notificacion.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
    notificacion.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notificacion);
    
    // Auto-eliminar después de 3 segundos
    setTimeout(() => {
        if (notificacion.parentNode) {
            notificacion.remove();
        }
    }, 3000);
}
    </script>
    <!-- Modal Ayuda -->
<div class="modal fade" id="modalAyuda" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-info-circle"></i> Acerca de Free Open Worship</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h4 class="mb-1">Free Open Worship <span class="badge bg-secondary">v1.0</span></h4>
                <p class="mb-2"><strong>Creado por:</strong> Daniel Quinde</p>
                <div class="alert alert-success">
                    <p>
                        Este software fue creado con mucho amor y dedicación para bendecir a la iglesia y facilitar la adoración. 
                        Me siento muy feliz de poder aportar con una herramienta libre, sencilla y útil para todos los que sirven en la obra de Dios.
                    </p>
                    <p>
                        <strong>¡Que sea de mucha bendición para tu congregación!</strong>
                    </p>
                </div>
                <p>
                    <i class="bi bi-whatsapp text-success"></i>
                    <strong>Contacto WhatsApp:</strong> 
                    <a href="https://wa.me/5930968568404" target="_blank">+593 096 856 8404</a>
                </p>
                <div class="alert alert-info">
                    <i class="bi bi-unlock"></i>
                    <strong>Este software y su código están disponibles gratuitamente para cualquier iglesia que lo quiera usar localmente, sin necesidad de internet.</strong>
                    <br>
                    Si necesitas ayuda para instalarlo o personalizarlo, ¡escríbeme!
                </div>
                <p class="text-center text-muted mb-0" style="font-size:0.95em;">
                    <i class="bi bi-heart-fill text-danger"></i> Hecho con cariño para la familia de la fe.
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>