<?php
// ============================================================
//  guias.php — Guías de viaje | Ruta Nómada
//  Muestra guías curadas con itinerarios y PDFs descargables
// ============================================================
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Guías hardcodeadas (sin BD)
$guias = [
    [
        'id' => 1,
        'titulo' => '5 días en París: lo imprescindible',
        'destino' => 'París',
        'pais' => 'Francia',
        'descripcion' => 'Una guía completa para descubrir París en 5 días, incluyendo monumentos icónicos, museos de clase mundial y gastronomía francesa.',
        'duracion' => 5,
        'dificultad' => 'Moderada',
        'costo' => '$800-1500',
        'pdf' => 'paris_5dias.pdf',
        'imagen' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ1V1nRdEDgCUsy802xZkSnnQ5JV4ZGe-TutXD6u9mIQA&s=10'
    ],
    [
        'id' => 2,
        'titulo' => '3 días en Barcelona: ciudad y playa',
        'destino' => 'Barcelona',
        'pais' => 'España',
        'descripcion' => 'Explora Barcelona en 3 días: Gaudí, la Sagrada Familia, playas mediterráneas y la vibrante vida nocturna de la ciudad.',
        'duracion' => 3,
        'dificultad' => 'Fácil',
        'costo' => '$600-1000',
        'pdf' => 'barcelona_3dias.pdf',
        'imagen' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRgLcrl-iubbHOv4Xs9DVkPuRxxsH_aPUtS4fPeqnQWMgfvMjVGmEHotiQ&s=10'
    ],
    [
        'id' => 3,
        'titulo' => '7 días por Italia: Roma, Florencia y Venecia',
        'destino' => 'Italia',
        'pais' => 'Italia',
        'descripcion' => 'La ruta clásica italiana: descubre la historia romana, el renacimiento florentino y la magia de Venecia en una semana.',
        'duracion' => 7,
        'dificultad' => 'Moderada',
        'costo' => '$1200-2000',
        'pdf' => 'italia_7dias.pdf',
        'imagen' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTQampyEdupH3JqMn4NugkmkloH5STIV6J8414BgJ3K1Evyfk8sO-QOcG6a&s=10'
    ]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guías de viaje — Ruta Nómada</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
    <style>
        .guias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .guia-card {
            background: var(--white);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .guia-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .guia-imagen {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, #3e7986, #2b5760);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--white);
            overflow: hidden;
            background-size: cover;
            background-position: center;
        }

        .guia-imagen img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .guia-body {
            padding: 1.5rem;
        }

        .guia-destino {
            font-size: 0.8rem;
            color: var(--text-link);
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .guia-titulo {
            font-family: var(--font-display);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.8rem;
        }

        .guia-descripcion {
            font-size: 0.9rem;
            color: var(--gray-500);
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .guia-info {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
        }

        .guia-info-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .guia-info-label {
            color: var(--gray-500);
        }

        .guia-info-value {
            font-weight: 600;
            color: var(--text-dark);
        }

        .guia-acciones {
            display: flex;
            gap: 0.8rem;
        }

        .btn-guia-pdf {
            flex: 1;
            padding: 0.7rem 1rem;
            background: linear-gradient(135deg, var(--teal-700), var(--teal-900));
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
        }

        .btn-guia-pdf:hover {
            background: linear-gradient(135deg, var(--teal-800), #041f2a);
            box-shadow: 0 4px 12px rgba(6, 39, 56, 0.3);
        }
    </style>
</head>
<body class="dashboard-page">

<?php $topbar_active = 'guias'; include __DIR__ . '/includes/topbar.php'; ?>

<div class="layout">
    <main class="main-content">
        <div class="dashboard-header">
            <h2 class="dashboard-title">Guías de viaje</h2>
            <p class="dashboard-subtitle">Itinerarios curados y consejos para descubrir cada destino.</p>
        </div>

        <div class="guias-grid">
            <?php foreach ($guias as $guia): ?>
                <div class="guia-card">
                    <div class="guia-imagen">
                        <?php if (!empty($guia['imagen'])): ?>
                            <img src="<?= htmlspecialchars($guia['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($guia['titulo'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            🗺️
                        <?php endif; ?>
                    </div>
                    <div class="guia-body">
                        <div class="guia-destino"><?= htmlspecialchars($guia['destino'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($guia['pais'], ENT_QUOTES, 'UTF-8') ?></div>
                        <h3 class="guia-titulo"><?= htmlspecialchars($guia['titulo'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="guia-descripcion"><?= htmlspecialchars($guia['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                        
                        <div class="guia-info">
                            <div class="guia-info-item">
                                <span>⏱️ <?= (int)$guia['duracion'] ?> días</span>
                            </div>
                            <div class="guia-info-item">
                                <span>📊 <?= htmlspecialchars($guia['dificultad'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="guia-info-item">
                                <span class="guia-info-value">💰 <?= htmlspecialchars($guia['costo'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <div class="guia-acciones">
                            <a href="guias_pdf/<?= urlencode($guia['pdf']) ?>" target="_blank" class="btn-guia-pdf">
                                📄 PDF
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<script src="js/topbar.js"></script>
</body>
</html>
