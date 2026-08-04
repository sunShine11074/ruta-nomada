<?php
// ============================================================
//  includes/topbar.php — Topbar global (rediseño Figma) | Ruta Nómada
//  Logo · Buscador con categoría · Nav · Crear plan · Campana · Avatar
//  con menú desplegable (reemplaza al sidebar). Incluye la burbuja
//  flotante del futuro chatbot.
//
//  Uso en cada página:
//      $topbar_active = 'inicio' | 'misplanes' | 'guias' | null;
//      $topbar_search = true|false;  // false en Inicio (el buscador vive en inicio.php)
//      include __DIR__ . '/includes/topbar.php';
//  Requiere: sesión iniciada, db.php cargado y $user = $_SESSION['user'].
// ============================================================
require_once __DIR__ . '/user_topbar.php';

$topbar_active = $topbar_active ?? null;
$topbar_search = $topbar_search ?? true;   // en Inicio se oculta (variante "topbar 2")
$_tb_q   = htmlspecialchars(trim($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$_tb_tab = $_GET['tab'] ?? 'ciudad';
if (!in_array($_tb_tab, ['ciudad', 'hoteles', 'restaurantes', 'hacer'], true)) $_tb_tab = 'ciudad';
$_tb_link = function (string $key, string $href, string $label) use ($topbar_active): string {
    $on = $topbar_active === $key ? ' tb-nav__link--on' : '';
    return '<a href="' . $href . '" class="tb-nav__link' . $on . '">' . $label . '</a>';
};
?>
<header class="topbar tb<?= $topbar_search ? '' : ' tb--nosearch' ?>">
    <a class="tb-brand" href="inicio.php">
        <span class="logo-icon"></span>
        <span class="tb-brand__name">Ruta Nómada</span>
    </a>

    <!-- Buscador: texto + selector de categoría + lupa (oculto en Inicio) -->
    <?php if ($topbar_search): ?>
    <form class="tb-search" id="tbSearch" role="search">
        <input type="text" id="tbSearchInput" placeholder="Buscar una ciudad…"
               value="<?= $_tb_q ?>" aria-label="Buscar" autocomplete="off">
        <div class="tb-search__cat" id="tbCat">
            <button type="button" class="tb-search__catbtn" id="tbCatBtn" aria-haspopup="listbox">
                <span id="tbCatLabel" data-value="<?= $_tb_tab ?>"><?=
                    ['ciudad' => 'Ciudad', 'hoteles' => 'Hoteles', 'restaurantes' => 'Restaurantes', 'hacer' => 'Cosas que hacer'][$_tb_tab]
                ?></span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <ul class="tb-search__catlist" id="tbCatList" hidden>
                <li data-value="ciudad">Ciudad</li>
                <li data-value="hoteles">Hoteles</li>
                <li data-value="restaurantes">Restaurantes</li>
                <li data-value="hacer">Cosas que hacer</li>
            </ul>
        </div>
        <button type="submit" class="tb-search__go" aria-label="Buscar">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        </button>
    </form>
    <?php endif; ?>

    <nav class="tb-nav">
        <?= $_tb_link('inicio',    'inicio.php',  'Inicio') ?>
        <?= $_tb_link('misplanes', 'mis_planes.php', 'Mis planes') ?>
        <?= $_tb_link('guias',     'guias.php',      'Guías de viaje') ?>
    </nav>

    <div class="tb-right">
        <a href="crear_plan.php" class="tb-crearplan">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M17 3h4v4"/><path d="M11 13L21 3"/><path d="M8 8h3M8 12h2M8 16h5"/></svg>
            <span>Crear plan de viaje</span>
        </a>
        <button class="tb-bell" aria-label="Notificaciones">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        </button>

        <!-- Avatar + menú desplegable (reemplaza al sidebar) -->
        <div class="tb-user" id="tbUser">
            <button class="tb-user__btn" id="tbUserBtn" aria-haspopup="menu" aria-expanded="false" aria-label="Menú de usuario">
                <?= renderAvatar('', $_topbar_foto, $_topbar_initial) ?>
                <span class="tb-user__dot" aria-hidden="true"></span>
            </button>
            <div class="tb-menu" id="tbMenu" hidden role="menu">
                <a href="profile.php" class="tb-menu__item" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm0 2c-3.3 0-8 1.7-8 5v2h16v-2c0-3.3-4.7-5-8-5z"/></svg>
                    Mi perfil
                </a>
                <a href="mis_guardados.php" class="tb-menu__item" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 3h12a1 1 0 0 1 1 1v17l-7-4-7 4V4a1 1 0 0 1 1-1z"/></svg>
                    Mis guardados
                </a>
                <a href="configuracion.php" class="tb-menu__item" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M19.4 13a7.5 7.5 0 0 0 .1-1 7.5 7.5 0 0 0-.1-1l2.1-1.6a.5.5 0 0 0 .1-.7l-2-3.4a.5.5 0 0 0-.6-.2l-2.5 1a7.7 7.7 0 0 0-1.7-1L14.5 2.5a.5.5 0 0 0-.5-.4h-4a.5.5 0 0 0-.5.4L9.2 5.1a7.7 7.7 0 0 0-1.7 1l-2.5-1a.5.5 0 0 0-.6.2l-2 3.4a.5.5 0 0 0 .1.7L4.6 11a7.5 7.5 0 0 0 0 2l-2.1 1.6a.5.5 0 0 0-.1.7l2 3.4c.1.2.4.3.6.2l2.5-1a7.7 7.7 0 0 0 1.7 1l.3 2.6c0 .2.2.4.5.4h4c.2 0 .5-.2.5-.4l.3-2.6a7.7 7.7 0 0 0 1.7-1l2.5 1c.2.1.5 0 .6-.2l2-3.4a.5.5 0 0 0-.1-.7L19.4 13zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/></svg>
                    Configuración
                </a>
                <a href="inicio.php?logout=1" class="tb-menu__item tb-menu__item--danger" role="menuitem">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Chatbot flotante: burbuja abajo a la izquierda + panel del asistente -->
<?php include __DIR__ . '/asistente.php'; ?>
