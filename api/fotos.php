<?php
// ============================================================
//  api/fotos.php — Las fotos que sube cada persona | Ruta Nómada
//
//  POST {plan_id, action: listar}            → {ok, fotos:[...]}
//  POST multipart {plan_id, action: subir}   → {ok, foto:{...}}
//       + archivo en el campo 'imagen'
//  POST {plan_id, action: borrar, id}        → {ok}
//
//  Son del USUARIO, no del plan: el frame dice «Tus fotos», así que
//  una foto subida para un viaje se puede reutilizar en otro. Por eso
//  se filtra siempre por usuario_id y nunca por plan_id.
//
//  El plan_id se pide igualmente para exigir rol de editor: subir una
//  foto es un paso de cambiar la portada, y quien sólo puede leer un
//  plan no debería poder dejar archivos en el servidor.
// ============================================================
require_once __DIR__ . '/../includes/plan_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') apiFail('Método no permitido.', 405);

// En multipart el cuerpo no es JSON; apiBody() ya cae en $_POST.
$in = apiBody();
csrfCheck($in);
$acc = planAccess((int)($in['plan_id'] ?? 0), 'editor');
$uid = (int)$acc['user_id'];
$db  = getDB();

$DIR_REL = 'img/portadas';
$DIR_ABS = __DIR__ . '/../' . $DIR_REL;

// Lo que la rejilla necesita de cada foto
function filaFoto(array $r): array
{
    return ['id' => (int)$r['id'], 'ruta' => (string)$r['ruta']];
}

switch ($in['action'] ?? '') {

    case 'listar': {
        $st = $db->prepare('SELECT id, ruta FROM usuario_fotos WHERE usuario_id = ? ORDER BY subida_en DESC, id DESC LIMIT 60');
        $st->execute([$uid]);
        apiJson(['ok' => true, 'fotos' => array_map('filaFoto', $st->fetchAll())]);
    }

    case 'subir': {
        $f = $_FILES['imagen'] ?? null;
        if (!$f || !isset($f['error'])) apiFail('No llegó ninguna imagen.');
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            apiFail('La imagen es demasiado grande.');
        }
        if ($f['error'] !== UPLOAD_ERR_OK) apiFail('No se pudo recibir la imagen.');

        // 5 MB. El navegador ya la reduce con <canvas> antes de mandarla
        // (ver _fotoSubir en js/plan_logic.js), así que llegar a este
        // tope significa que algo se saltó ese paso.
        if ($f['size'] > 5 * 1024 * 1024) apiFail('La imagen no puede pesar más de 5 MB.');

        // ── Que sea una imagen DE VERDAD ─────────────────────
        // Se comprueba el CONTENIDO, no la extensión ni el
        // Content-Type: los dos los elige quien sube el archivo. Un
        // .php renombrado a .jpg no pasa de aquí.
        //
        // exif_imagetype lee la firma de los primeros bytes; finfo
        // vuelve a mirar el contenido por su cuenta. Se exige que las
        // dos coincidan con la lista blanca.
        $permitidos = [
            IMAGETYPE_JPEG => ['image/jpeg', 'jpg'],
            IMAGETYPE_PNG  => ['image/png',  'png'],
            IMAGETYPE_WEBP => ['image/webp', 'webp'],
        ];
        $tipo = @exif_imagetype($f['tmp_name']);
        if ($tipo === false || !isset($permitidos[$tipo])) {
            apiFail('El archivo no es una imagen JPG, PNG o WEBP.');
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($f['tmp_name']);
        if ($mime !== $permitidos[$tipo][0]) {
            apiFail('El archivo no es una imagen válida.');
        }
        $ext = $permitidos[$tipo][1];

        if (!is_dir($DIR_ABS) && !@mkdir($DIR_ABS, 0755, true)) {
            error_log('fotos.php: no se pudo crear ' . $DIR_ABS);
            apiFail('No se pudo guardar la imagen.', 500);
        }

        // Nombre aleatorio, sin nada de lo que venga del cliente. El
        // nombre original puede traer rutas ("../"), caracteres raros
        // o una segunda extensión, y no aporta nada.
        $nombre = bin2hex(random_bytes(16)) . '.' . $ext;
        $destino = $DIR_ABS . '/' . $nombre;
        if (!move_uploaded_file($f['tmp_name'], $destino)) {
            error_log('fotos.php: move_uploaded_file falló hacia ' . $destino);
            apiFail('No se pudo guardar la imagen.', 500);
        }
        @chmod($destino, 0644);

        $ruta = $DIR_REL . '/' . $nombre;
        try {
            $st = $db->prepare('INSERT INTO usuario_fotos (usuario_id, ruta) VALUES (?, ?)');
            $st->execute([$uid, $ruta]);
            $id = (int)$db->lastInsertId();
        } catch (PDOException $e) {
            // Si la fila no entra, el archivo sobra: se borra para no
            // dejar huérfanos que nadie va a poder ver ni limpiar.
            @unlink($destino);
            error_log('fotos.php insert: ' . $e->getMessage());
            apiFail('No se pudo registrar la imagen.', 500);
        }

        apiJson(['ok' => true, 'foto' => ['id' => $id, 'ruta' => $ruta]]);
    }

    case 'borrar': {
        $id = (int)($in['id'] ?? 0);
        // El WHERE lleva usuario_id: nadie borra las fotos de otro
        // aunque acierte el id.
        $st = $db->prepare('SELECT ruta FROM usuario_fotos WHERE id = ? AND usuario_id = ? LIMIT 1');
        $st->execute([$id, $uid]);
        $ruta = $st->fetchColumn();
        if ($ruta === false) apiFail('Esa foto no existe.', 404);

        // El archivo primero: si se borrara la fila antes y fallara el
        // unlink, quedaría un archivo que ya nadie sabe que existe.
        $abs = __DIR__ . '/../' . $ruta;
        if (is_file($abs)) @unlink($abs);
        $db->prepare('DELETE FROM usuario_fotos WHERE id = ? AND usuario_id = ?')->execute([$id, $uid]);
        apiJson(['ok' => true]);
    }

    default:
        apiFail('Acción desconocida.');
}
