<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/currency.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}



$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db_check = checkDBConnection();
if ($db_check['ok'] && $id > 0) {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM destinos WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $destino = $stmt->fetch();
}

$user = $_SESSION['user'];
$inicial = mb_strtoupper(mb_substr($user['nombre'],0,1));
require_once __DIR__ . '/includes/user_topbar.php';
// Manejar la acción de guardar viaje vía AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_viaje') {
    header('Content-Type: application/json');
    $destino_id = isset($_POST['destino_id']) ? (int)$_POST['destino_id'] : 0;
    
    if ($destino_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de destino inválido']);
        exit;
    }
    
    try {
        $db = getDB();
        
        // Verificar que el destino existe
        $stmt_destino = $db->prepare('SELECT id FROM destinos WHERE id = ? LIMIT 1');
        $stmt_destino->execute([$destino_id]);
        if (!$stmt_destino->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Destino no encontrado']);
            exit;
        }
        
        // Verificar si el viaje ya existe
        $stmt_check = $db->prepare('SELECT idviaje_usuario FROM viajes_usuario WHERE usuario_id = ? AND destino_id = ? LIMIT 1');
        $stmt_check->execute([$_SESSION['user']['id'], $destino_id]);
        
        if ($stmt_check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este viaje ya está en tu lista']);
            exit;
        }
        
        // Guardar el viaje
        $stmt_insert = $db->prepare('INSERT INTO viajes_usuario (usuario_id, destino_id, fecha) VALUES (?, ?, NOW())');
        $stmt_insert->execute([$_SESSION['user']['id'], $destino_id]);
        
        echo json_encode(['success' => true, 'message' => '¡Viaje guardado exitosamente!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $destino ? htmlspecialchars($destino['nombre'],ENT_QUOTES,'UTF-8') . ' — Ruta Nómada' : 'Destino — Ruta Nómada' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="topbar.css">
</head>
<body class="dashboard-page">
<?php $topbar_active = null; include __DIR__ . '/includes/topbar.php'; ?>
<div class="layout">
  <main class="main-content">
    <?php if (!$destino): ?>
      <div class="alert alert--form">Destino no encontrado o error de conexión.</div>
    <?php else: ?>
      <div style="display:flex;gap:1rem;align-items:flex-start;">
        <div style="flex:1;">
          <div style="height:200px;background:linear-gradient(135deg,#d4e4d8,#e8ead0);border-radius:10px;margin-bottom:1rem;display:flex;align-items:center;justify-content:center;font-size:4rem;"><?= htmlspecialchars($destino['icon'] ?? '🏝',ENT_QUOTES,'UTF-8') ?></div>
          <h1 style="font-family:var(--font-display);font-size:1.6rem;margin-bottom:.2rem;"><?= htmlspecialchars($destino['nombre'],ENT_QUOTES,'UTF-8') ?> <span style="font-size:.9rem;color:var(--gray-500);">⭐ <?= htmlspecialchars($destino['valoracion'],ENT_QUOTES,'UTF-8') ?></span></h1>
          <p style="color:var(--gray-700);margin-bottom:1rem;"><?= nl2br(htmlspecialchars($destino['descripcion'],ENT_QUOTES,'UTF-8')) ?></p>
          <h4>Lugares destacados</h4>
          <div style="display:flex;gap:.6rem;margin-top:.6rem;">
            <div style="background:var(--white);padding:.6rem;border-radius:8px;">Zona Hotelera</div>
            <div style="background:var(--white);padding:.6rem;border-radius:8px;">Chichén Itzá</div>
            <div style="background:var(--white);padding:.6rem;border-radius:8px;">Isla Mujeres</div>
          </div>
        </div>
        <aside style="width:320px;background:var(--white);padding:1rem;border-radius:var(--radius-md);">
          <div style="font-weight:700;font-size:1.05rem;">Precio desde · por persona</div>
          <div id="precio-mxn" style="font-size:1.35rem;color:var(--text-dark);font-weight:700;margin:8px 0;"><?= priceInUserCurrency($destino['precio_desde']) ?></div>

          <!-- ═══ Frankfurter Currency Converter ═══ -->
          <div id="currency-converter" style="background:linear-gradient(135deg,#f0f7f4,#eef4f0);border:1.5px solid #c8dccb;border-radius:var(--radius-md);padding:.85rem;margin:10px 0 14px;">
            <div style="display:flex;align-items:center;gap:.4rem;margin-bottom:.6rem;">
              <span style="font-size:1.1rem;">💱</span>
              <span style="font-weight:600;font-size:.88rem;color:var(--text-dark);">Convertir a otra moneda</span>
            </div>
            <div id="currency-dropdown" style="position:relative;width:100%;">
              <button type="button" id="currency-toggle" style="width:100%;padding:.55rem .7rem;border:1.5px solid var(--gray-300);border-radius:var(--radius-sm);font-family:var(--font-body);font-size:.88rem;color:var(--text-dark);background:var(--white);cursor:pointer;outline:none;transition:border-color .18s,box-shadow .18s;display:flex;align-items:center;justify-content:space-between;gap:.5rem;">
                <span id="currency-label" style="display:flex;align-items:center;gap:.5rem;color:var(--gray-500);">Selecciona el tipo de moneda</span>
                <span style="font-size:.7rem;color:var(--gray-500);">▼</span>
              </button>
              <ul id="currency-list" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;background:var(--white);border:1.5px solid var(--gray-300);border-radius:var(--radius-sm);box-shadow:0 6px 18px rgba(0,0,0,.12);z-index:50;max-height:260px;overflow-y:auto;list-style:none;margin:0;padding:4px 0;">
                <li class="cur-opt" data-value="" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;color:var(--gray-500);transition:background .12s;">Seleccionar moneda</li>
                <li class="cur-opt" data-value="MXN" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/mx.png" width="16" height="12" alt="MX" style="border-radius:2px;"> <strong>MXN</strong> · Peso mexicano</li>
                <li class="cur-opt" data-value="USD" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/us.png" width="16" height="12" alt="US" style="border-radius:2px;"> <strong>USD</strong> · Dólar estadounidense</li>
                <li class="cur-opt" data-value="EUR" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/eu.png" width="16" height="12" alt="EU" style="border-radius:2px;"> <strong>EUR</strong> · Euro</li>
                <li class="cur-opt" data-value="GBP" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/gb.png" width="16" height="12" alt="GB" style="border-radius:2px;"> <strong>GBP</strong> · Libra esterlina</li>
                <li class="cur-opt" data-value="CAD" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/ca.png" width="16" height="12" alt="CA" style="border-radius:2px;"> <strong>CAD</strong> · Dólar canadiense</li>
                <li class="cur-opt" data-value="JPY" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/jp.png" width="16" height="12" alt="JP" style="border-radius:2px;"> <strong>JPY</strong> · Yen japonés</li>
                <li class="cur-opt" data-value="BRL" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/br.png" width="16" height="12" alt="BR" style="border-radius:2px;"> <strong>BRL</strong> · Real brasileño</li>
                <li class="cur-opt" data-value="COP" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/co.png" width="16" height="12" alt="CO" style="border-radius:2px;"> <strong>COP</strong> · Peso colombiano</li>
                <li class="cur-opt" data-value="ARS" style="padding:.5rem .7rem;cursor:pointer;font-size:.86rem;display:flex;align-items:center;gap:.5rem;transition:background .12s;"><img src="https://flagcdn.com/16x12/ar.png" width="16" height="12" alt="AR" style="border-radius:2px;"> <strong>ARS</strong> · Peso argentino</li>
              </ul>
            </div>
            <div id="conversion-result" style="display:none;margin-top:.65rem;">
              <div id="converted-amount" style="font-size:1.15rem;font-weight:700;color:var(--teal-800);"></div>
              <div id="exchange-rate" style="font-size:.76rem;color:var(--gray-500);margin-top:2px;"></div>
              <div id="rate-date" style="font-size:.72rem;color:var(--gray-500);margin-top:1px;"></div>
            </div>
            <div id="conversion-loading" style="display:none;margin-top:.6rem;text-align:center;font-size:.84rem;color:var(--gray-500);">⏳ Consultando tipo de cambio…</div>
            <div id="conversion-error" style="display:none;margin-top:.6rem;padding:.45rem;border-radius:6px;background:#f8d7da;color:#721c24;font-size:.8rem;text-align:center;"></div>
          </div>
          <!-- ═══ End Currency Converter ═══ -->

          <button class="btn-primary" style="width:100%;">Cotizar este viaje</button>
          <button id="btn-guardar" class="btn-guardar" style="width:100%;margin-top:.6rem;background:#dfeee6;border:1px solid #c8dccb;padding:.6rem;border-radius:8px;cursor:pointer;">
            Guardar plan
          </button>
          <div id="mensaje-guardado" style="margin-top:.6rem;padding:.6rem;border-radius:8px;display:none;text-align:center;font-size:.9rem;"></div>
        </aside>
      </div>
    <?php endif; ?>
  </main>
</div>

<script>
// ═══════════════════════════════════════════════════════════
//  Frankfurter API — Currency Converter (with flagcdn flags)
// ═══════════════════════════════════════════════════════════
(function() {
    const PRICE_MXN = <?= (float)($destino['precio_desde'] ?? 0) ?>;
    const toggle    = document.getElementById('currency-toggle');
    const label     = document.getElementById('currency-label');
    const list      = document.getElementById('currency-list');
    const options   = document.querySelectorAll('.cur-opt');
    const resultDiv = document.getElementById('conversion-result');
    const amountEl  = document.getElementById('converted-amount');
    const rateEl    = document.getElementById('exchange-rate');
    const dateEl    = document.getElementById('rate-date');
    const loadingEl = document.getElementById('conversion-loading');
    const errorEl   = document.getElementById('conversion-error');

    // Currency symbols & country codes for flags
    const currencies = {
        MXN: { sym: '$',  cc: 'mx', name: 'Peso mexicano' },
        USD: { sym: '$',  cc: 'us', name: 'Dólar estadounidense' },
        EUR: { sym: '€',  cc: 'eu', name: 'Euro' },
        GBP: { sym: '£',  cc: 'gb', name: 'Libra esterlina' },
        CAD: { sym: 'C$', cc: 'ca', name: 'Dólar canadiense' },
        JPY: { sym: '¥',  cc: 'jp', name: 'Yen japonés' },
        BRL: { sym: 'R$', cc: 'br', name: 'Real brasileño' },
        COP: { sym: '$',  cc: 'co', name: 'Peso colombiano' },
        ARS: { sym: '$',  cc: 'ar', name: 'Peso argentino' },
    };

    let selectedValue = '';
    let isOpen = false;

    // Toggle dropdown open/close
    toggle.addEventListener('click', function(e) {
        e.stopPropagation();
        isOpen = !isOpen;
        list.style.display = isOpen ? 'block' : 'none';
        toggle.style.borderColor = isOpen ? 'var(--teal-700)' : 'var(--gray-300)';
        toggle.style.boxShadow = isOpen ? '0 0 0 3px rgba(27,58,64,.1)' : 'none';
    });

    // Close on outside click
    document.addEventListener('click', function() {
        if (isOpen) {
            isOpen = false;
            list.style.display = 'none';
            toggle.style.borderColor = 'var(--gray-300)';
            toggle.style.boxShadow = 'none';
        }
    });

    // Hover effect on options
    options.forEach(function(opt) {
        opt.addEventListener('mouseenter', function() { this.style.background = '#f0f7f4'; });
        opt.addEventListener('mouseleave', function() { this.style.background = ''; });
    });

    // Option click
    options.forEach(function(opt) {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            selectedValue = this.dataset.value;
            isOpen = false;
            list.style.display = 'none';
            toggle.style.borderColor = 'var(--gray-300)';
            toggle.style.boxShadow = 'none';

            // Update label
            if (!selectedValue) {
                label.innerHTML = '<span style="color:var(--gray-500);">— Selecciona moneda —</span>';
            } else {
                const cur = currencies[selectedValue];
                label.innerHTML = '<img src="https://flagcdn.com/16x12/' + cur.cc + '.png" width="16" height="12" style="border-radius:2px;"> <strong>' + selectedValue + '</strong> · ' + cur.name;
                label.style.color = 'var(--text-dark)';
            }

            // Trigger conversion
            convertCurrency(selectedValue);
        });
    });

    // Conversion logic
    async function convertCurrency(target) {
        resultDiv.style.display = 'none';
        loadingEl.style.display = 'none';
        errorEl.style.display   = 'none';

        if (!target || target === 'MXN') {
            // If MXN selected, show the original price as-is
            if (target === 'MXN') {
                amountEl.textContent = '= $' + PRICE_MXN.toLocaleString('es-MX', { minimumFractionDigits: 0 }) + ' MXN';
                rateEl.textContent   = 'Moneda base del destino';
                dateEl.textContent   = '';
                resultDiv.style.display = 'block';
                animateResult();
            }
            return;
        }

        loadingEl.style.display = 'block';

        try {
            const res = await fetch(
                `https://api.frankfurter.dev/v2/rate/MXN/${target}`
            );

            if (!res.ok) throw new Error('No se pudo obtener el tipo de cambio.');

            const data = await res.json();
            const rate = data.rate;
            const converted = (PRICE_MXN * rate);

            const cur = currencies[target];
            const sym = cur ? cur.sym : '';
            let formatted;
            if (target === 'JPY' || target === 'COP' || target === 'ARS') {
                formatted = sym + Math.round(converted).toLocaleString('es-MX');
            } else {
                formatted = sym + converted.toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            amountEl.textContent = '≈ ' + formatted + ' ' + target;
            rateEl.textContent   = 'Tasa: 1 MXN = ' + rate.toFixed(6) + ' ' + target;
            dateEl.textContent   = '📅 Actualizado: ' + (data.date || new Date().toLocaleDateString('es-MX'));

            loadingEl.style.display = 'none';
            resultDiv.style.display = 'block';
            animateResult();

        } catch (err) {
            loadingEl.style.display = 'none';
            errorEl.textContent = '⚠ ' + (err.message || 'Error al consultar el tipo de cambio.');
            errorEl.style.display = 'block';
        }
    }

    function animateResult() {
        resultDiv.style.opacity = '0';
        resultDiv.style.transform = 'translateY(4px)';
        requestAnimationFrame(() => {
            resultDiv.style.transition = 'opacity .3s, transform .3s';
            resultDiv.style.opacity = '1';
            resultDiv.style.transform = 'translateY(0)';
        });
    }
})();

// ═══════════════════════════════════════════════════════════
//  Guardar viaje (existing functionality)
// ═══════════════════════════════════════════════════════════
document.getElementById('btn-guardar').addEventListener('click', async function(e) {
    e.preventDefault();
    const boton = this;
    const mensajeDiv = document.getElementById('mensaje-guardado');
    
    boton.disabled = true;
    boton.style.opacity = '0.6';
    
    try {
        const formData = new FormData();
        formData.append('action', 'save_viaje');
        formData.append('destino_id', <?= $destino['id'] ?? 0 ?>);
        
        const response = await fetch('destino.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        mensajeDiv.style.display = 'block';
        if (data.success) {
            mensajeDiv.style.background = '#d4edda';
            mensajeDiv.style.color = '#155724';
            mensajeDiv.style.border = '1px solid #c3e6cb';
            mensajeDiv.textContent = '✓ ' + data.message;
            boton.style.opacity = '0.5';
            boton.disabled = true;
        } else {
            mensajeDiv.style.background = '#f8d7da';
            mensajeDiv.style.color = '#721c24';
            mensajeDiv.style.border = '1px solid #f5c6cb';
            mensajeDiv.textContent = '✗ ' + data.message;
            boton.disabled = false;
            boton.style.opacity = '1';
        }
    } catch (error) {
        mensajeDiv.style.display = 'block';
        mensajeDiv.style.background = '#f8d7da';
        mensajeDiv.style.color = '#721c24';
        mensajeDiv.style.border = '1px solid #f5c6cb';
        mensajeDiv.textContent = '✗ Error al guardar el viaje';
        boton.disabled = false;
        boton.style.opacity = '1';
    }
});
</script>
<script src="js/topbar.js"></script>
</body>
</html>
