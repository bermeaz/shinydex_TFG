<?php
session_set_cookie_params(['path' => '/', 'httponly' => true]);
session_start();

if (isset($_SESSION['remember_me']) && $_SESSION['remember_me']) {
    setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/', '', false, true);
}

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

include 'config.php';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$versionesTraducidas = [
    'red' => 'Rojo',
    'blue' => 'Azul',
    'yellow' => 'Amarillo',
    'gold' => 'Oro',
    'silver' => 'Plata',
    'crystal' => 'Cristal',
    'ruby' => 'Rubí',
    'sapphire' => 'Zafiro',
    'emerald' => 'Esmeralda',
    'firered' => 'Rojo Fuego',
    'leafgreen' => 'Verde Hoja',
    'diamond' => 'Diamante',
    'pearl' => 'Perla',
    'platinum' => 'Platino',
    'heartgold' => 'HeartGold',
    'soulsilver' => 'SoulSilver',
    'black' => 'Negro',
    'white' => 'Blanco',
    'black-2' => 'Negro 2',
    'white-2' => 'Blanco 2',
    'x' => 'X',
    'y' => 'Y',
    'omega-ruby' => 'Rubí Omega',
    'alpha-sapphire' => 'Zafiro Alfa',
    'sun' => 'Sol',
    'moon' => 'Luna',
    'ultra-sun' => 'Ultra Sol',
    'ultra-moon' => 'Ultra Luna',
    'lets-go-pikachu' => 'Let\'s Go Pikachu',
    'lets-go-eevee' => 'Let\'s Go Eevee',
    'sword' => 'Espada',
    'shield' => 'Escudo',
    'brilliant-diamond' => 'Diamante Brillante',
    'shining-pearl' => 'Perla Reluciente',
    'legends-arceus' => 'Leyendas: Arceus',
    'scarlet' => 'Escarlata',
    'violet' => 'Púrpura',
    'legends-za' => 'Leyendas: ZA'
];

 $juegos8192 = [
    'oro', 'plata', 'cristal', 'rubí', 'zafiro', 'esmeralda', 'rojo fuego', 'verde hoja',
    'diamante', 'perla', 'platino', 'heartgold', 'soulsilver', 'negro', 'blanco', 'negro 2', 'blanco 2',
    'gold', 'silver', 'crystal', 'ruby', 'sapphire', 'emerald', 'firered', 'leafgreen',
    'diamond', 'pearl', 'platinum', 'black', 'white', 'black-2', 'white-2'
];

 $juegos4096 = [
    'x', 'y', 'rubí omega', 'zafiro alfa', 'sol', 'luna', 'ultra sol', 'ultra luna',
    'let\'s go pikachu', 'let\'s go eevee', 'espada', 'escudo', 'diamante brillante', 'perla reluciente',
    'leyendas: arceus', 'escarlata', 'púrpura', 'leyendas za',
    'omega-ruby', 'alpha-sapphire', 'sun', 'moon', 'ultra-sun', 'ultra-moon',
    'lets-go-pikachu', 'lets-go-eevee', 'sword', 'shield', 'brilliant-diamond', 'shining-pearl',
    'legends-arceus', 'scarlet', 'violet', 'legends-za'
];

$stmt = $pdo->prepare("SELECT * FROM pokemon_atrapados WHERE usuario_id = ? ORDER BY LOWER(TRIM(juego)) ASC, pokedex_numero ASC");
$stmt->execute([$_SESSION['user_id']]);
$pokemons = $stmt->fetchAll(PDO::FETCH_ASSOC);

global $global_encuentros;
$global_encuentros = 0;
$global_under = 0;
$global_over = 0;
$estadisticasPorGrupo = [
    '8192' => [
        'label' => 'Probabilidad 1/8192',
        'total' => 0,
        'encuentros' => 0,
        'under' => 0,
        'over' => 0,
        'games' => []
    ],
    '4096' => [
        'label' => 'Probabilidad 1/4096',
        'total' => 0,
        'encuentros' => 0,
        'under' => 0,
        'over' => 0,
        'games' => []
    ]
];

foreach ($pokemons as $pokemon) {
    $juego_normalizado = strtolower(trim($pokemon['juego']));
    $es8192 = in_array($juego_normalizado, $juegos8192, true);
    $es4096 = in_array($juego_normalizado, $juegos4096, true);
    $prob_shiny = $es8192 ? 8192 : 4096;
    $grupo = $es8192 ? '8192' : '4096';

    if (!$es8192 && !$es4096) {
        $grupo = '4096';
        $prob_shiny = 4096;
    }
    $nombre_juego = traducirJuego($juego_normalizado, $versionesTraducidas);
    $encuentros = (int) $pokemon['encuentros'];
    $delta = $prob_shiny - $encuentros;
    $under = $delta > 0;
    $resultado = $under ? 'under' : 'over';

    if (!isset($estadisticasPorGrupo[$grupo]['games'][$nombre_juego])) {
        $estadisticasPorGrupo[$grupo]['games'][$nombre_juego] = [
            'under' => [],
            'over' => [],
            'total' => 0,
            'encuentros' => 0
        ];
    }

    $estadisticasPorGrupo[$grupo]['total']++;
    $estadisticasPorGrupo[$grupo]['encuentros'] += $encuentros;
    $estadisticasPorGrupo[$grupo][$resultado]++;
    $estadisticasPorGrupo[$grupo]['games'][$nombre_juego]['total']++;
    $estadisticasPorGrupo[$grupo]['games'][$nombre_juego]['encuentros'] += $encuentros;
    $estadisticasPorGrupo[$grupo]['games'][$nombre_juego][$resultado][] = array_merge($pokemon, [
        'delta' => $delta,
        'resultado' => $resultado,
        'threshold' => $prob_shiny
    ]);

    $global_encuentros += $encuentros;
    if ($under) {
        $global_under++;
    } else {
        $global_over++;
    }
}

foreach ($estadisticasPorGrupo as &$grupoStats) {
    ksort($grupoStats['games']);
}
unset($grupoStats);
 $ordenJuegos8192 = [
    'Oro', 'Plata', 'Cristal', 'Rubí', 'Zafiro', 'Esmeralda', 'Rojo Fuego', 'Verde Hoja',
    'Diamante', 'Perla', 'Platino', 'HeartGold', 'SoulSilver', 'Negro', 'Blanco', 'Negro 2', 'Blanco 2'
];
 $ordenJuegos4096 = [
    'X', 'Y', 'Rubí Omega', 'Zafiro Alfa', 'Sol', 'Luna', 'Ultra Sol', 'Ultra Luna',
    'Let\'s Go Pikachu', 'Let\'s Go Eevee', 'Espada', 'Escudo', 'Diamante Brillante',
    'Perla Reluciente', 'Leyendas: Arceus', 'Escarlata', 'Púrpura', 'Leyendas ZA'
];

foreach (['8192' => $ordenJuegos8192, '4096' => $ordenJuegos4096] as $grupo => $ordenJuegos) {
    $ordenado = [];
    foreach ($ordenJuegos as $nombreJuego) {
        if (isset($estadisticasPorGrupo[$grupo]['games'][$nombreJuego])) {
            $ordenado[$nombreJuego] = $estadisticasPorGrupo[$grupo]['games'][$nombreJuego];
        }
    }
    foreach ($estadisticasPorGrupo[$grupo]['games'] as $nombreJuego => $stats) {
        if (!isset($ordenado[$nombreJuego])) {
            $ordenado[$nombreJuego] = $stats;
        }
    }
    $estadisticasPorGrupo[$grupo]['games'] = $ordenado;
}
function traducirJuego($juego, $map)
{
    $key = strtolower(trim($juego));
    if (isset($map[$key])) {
        return $map[$key];
    }

    $fallback = preg_replace('/\s+/', ' ', trim($juego));
    return mb_convert_case($fallback, MB_CASE_TITLE, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShinyDex - Estadísticas Shiny</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <div>
            <h1>📊 Estadísticas Shiny</h1>
            <p style="color: var(--text-secondary); margin: 5px 0 0 0; font-size: 0.95em;">
                Análisis de tus Pokémon shiny para juegos 1/8192 y 1/4096.
            </p>
        </div>
        <div class="header-actions">
            <a href="coleccion.php" class="btn btn-secondary">📚 Mi Colección</a>
            <a href="juego.php" class="btn btn-secondary">🎮 Ver por Juego</a>
            <a href="index.php?logout=1" class="btn btn-gray">🚪 Logout</a>
            <div class="theme-toggle">
                <label for="themeToggle" style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                    <span>🌙 Modo Oscuro</span>
                    <input type="checkbox" id="themeToggle" class="toggle-checkbox">
                </label>
            </div>
        </div>
    </div>

    <div class="stats-summary">
        <div class="card summary-card total">
            <h2>Total de encuentros</h2>
            <p class="summary-number"><?= number_format($global_encuentros, 0, ',', '.') ?></p>
        </div>
        <div class="card summary-card under">
            <h2>Under odds</h2>
            <p class="summary-number"><?= $global_under ?></p>
        </div>
        <div class="card summary-card over">
            <h2>Over odds</h2>
            <p class="summary-number"><?= $global_over ?></p>
        </div>
    </div>

    <?php if (empty($pokemons)): ?>
        <div class="card stats-game-card no-data-card">
            <h2>No hay datos para estas generaciones</h2>
            <p class="group-meta">No hay Pokémon shiny registrados.</p>
        </div>
    <?php else: ?>
        <?php foreach (['8192', '4096'] as $grupo): ?>
            <?php $groupStats = $estadisticasPorGrupo[$grupo]; ?>
            <div class="stats-group">
                <h2>📌 <?= htmlspecialchars($groupStats['label']) ?></h2>
                <p class="group-meta">Total de Pokémon: <?= $groupStats['total'] ?> — Encuentros totales: <?= number_format($groupStats['encuentros'], 0, ',', '.') ?> — Under odds: <?= $groupStats['under'] ?> — Over odds: <?= $groupStats['over'] ?></p>

                <?php if (empty($groupStats['games'])): ?>
                    <div class="card stats-game-card">
                        <p class="group-meta">No hay Pokémon shiny registrados con esta probabilidad.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($groupStats['games'] as $juego => $stats): ?>
                        <div class="card stats-game-card">
                            <h3>🎯 <?= htmlspecialchars($juego) ?></h3>
                            <p class="group-meta">Total de Pokémon: <?= $stats['total'] ?> — Encuentros totales: <?= number_format($stats['encuentros'], 0, ',', '.') ?></p>

                            <div class="stats-game-grid">
                                <div class="card stats-game-subcard">
                                    <h4>Under odds</h4>
                                    <?php if (empty($stats['under'])): ?>
                                        <p class="group-meta">No hay Pokémon under odds en este juego.</p>
                                    <?php else: ?>
                                        <?php foreach ($stats['under'] as $pokemon): ?>
                                            <div class="card stats-item-card">
                                                <div class="stats-item-row">
                                                    <div>
                                                        <strong>#<?= htmlspecialchars($pokemon['pokedex_numero']) ?> <?= htmlspecialchars($pokemon['nombre']) ?></strong>
                                                        <div class="item-meta">Encuentros: <?= htmlspecialchars($pokemon['encuentros']) ?> — Estadística: +<?= number_format($pokemon['delta'], 0, ',', '.') ?></div>
                                                    </div>
                                                    <span class="badge badge-under">Under</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="card stats-game-subcard">
                                    <h4>Over odds</h4>
                                    <?php if (empty($stats['over'])): ?>
                                        <p class="group-meta">No hay Pokémon over odds en este juego.</p>
                                    <?php else: ?>
                                        <?php foreach ($stats['over'] as $pokemon): ?>
                                            <div class="card stats-item-card">
                                                <div class="stats-item-row">
                                                    <div>
                                                        <strong>#<?= htmlspecialchars($pokemon['pokedex_numero']) ?> <?= htmlspecialchars($pokemon['nombre']) ?></strong>
                                                        <div class="item-meta">Encuentros: <?= htmlspecialchars($pokemon['encuentros']) ?> — Estadística: <?= number_format($pokemon['delta'], 0, ',', '.') ?></div>
                                                    </div>
                                                    <span class="badge badge-over">Over</span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <script src="theme.js"></script>
    <footer class="site-footer">
        <div class="footer-inner">
            <p class="footer-text">Recursos utilizados de PokeAPI. Pokémon y todo su contenido son propiedad de Nintendo, Game Freak y The Pokémon Company.</p>
            <img class="footer-logo" src="https://raw.githubusercontent.com/PokeAPI/media/master/logo/pokeapi_256.png" alt="PokeAPI Logo">
        </div>
    </footer>
</body>
</html>
