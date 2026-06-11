<?php
    session_set_cookie_params(['path' => '/', 'httponly' => true]);
    session_start();

    if (isset($_SESSION['remember_me']) && $_SESSION['remember_me']) {
        setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/', '', false, true);
    }

    // Si no entras a esta página logeado, te devuelve al login automáticamente
    if (!isset($_SESSION['user_id'])) 
        {
            header("Location: index.php");
            exit;
        }

    // Configuración de la BD
    include "config.php";
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Obtener el juego seleccionado
    $juego_seleccionado = $_GET['juego'] ?? '';

    // Traducciones de versiones
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

    function normalize_game($juego)
        {
            if ($juego === null) {
                return '';
            }

            $juego = mb_strtolower(trim($juego), 'UTF-8');
            $juego = preg_replace('/\s+/u', ' ', $juego);
            $juego = str_replace(["_", '–', '—'], '-', $juego);
            $juego = str_replace(' ', '-', $juego);
            return trim($juego, '-');
        }

    function format_game_title($juego)
        {
            global $versionesTraducidas;
            if (isset($versionesTraducidas[$juego])) {
                return $versionesTraducidas[$juego];
            }

            $title = mb_convert_case(str_replace('-', ' ', $juego), MB_CASE_TITLE, 'UTF-8');
            return str_replace(' Za', ' ZA', $title);
        }

    // Obtener lista de juegos del usuario
    $stmt = $pdo->prepare("SELECT juego FROM pokemon_atrapados WHERE usuario_id = ? ORDER BY juego ASC");
    $stmt->execute([$_SESSION['user_id']]);
    $juegos_raw = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $juegos = [];
    foreach ($juegos_raw as $raw_juego) {
        $normalized = normalize_game($raw_juego);
        if ($normalized === '') {
            continue;
        }
        if (!isset($juegos[$normalized])) {
            $juegos[$normalized] = $normalized;
        }
    }
    $juegos = array_values($juegos);

    // Si se ha seleccionado un juego, obtener los Pokémon con paginación
    $pokemon_por_juego = [];
    $pagina_actual = max(1, (int) ($_GET['page'] ?? 1));
    $por_pagina = 30;
    $total_pokemon_juego = 0;
    $total_paginas = 1;

    $juego_seleccionado = normalize_game($juego_seleccionado);

    if ($juego_seleccionado) 
        {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM pokemon_atrapados WHERE usuario_id = ? AND REPLACE(REPLACE(LOWER(TRIM(juego)), ' ', '-'), '_', '-') = ?");
            $stmt->execute([$_SESSION['user_id'], $juego_seleccionado]);
            $total_pokemon_juego = (int) $stmt->fetchColumn();
            $total_paginas = max(1, (int) ceil($total_pokemon_juego / $por_pagina));
            $pagina_actual = min($pagina_actual, $total_paginas);
            $offset = ($pagina_actual - 1) * $por_pagina;

            $stmt = $pdo->prepare("SELECT * FROM pokemon_atrapados WHERE usuario_id = :usuario_id AND REPLACE(REPLACE(LOWER(TRIM(juego)), ' ', '-'), '_', '-') = :juego ORDER BY pokedex_numero ASC LIMIT :limit OFFSET :offset");
            $stmt->bindValue(':usuario_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(':juego', $juego_seleccionado, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $por_pagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $pokemon_por_juego = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShinyDex - PC de Pokémon</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <div>
            <h1>🎮 PC de Pokémon</h1>
            <p style="color: var(--text-secondary); margin: 5px 0 0 0; font-size: 0.95em;">Visualiza tus Pokémon por juego y navega entre páginas si hay más de 30 capturados.</p>
        </div>
        <div class="header-actions">
            <a href="coleccion.php" class="btn btn-secondary">📚 Mi Colección</a>
            <a href="estadisticas.php" class="btn btn-secondary">📈 Estadísticas</a>
            <a href="index.php?logout=1" class="btn btn-gray">🚪 Logout</a>
            <div class="theme-toggle">
                <label for="themeToggle" style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                    <span>🌙 Modo Oscuro</span>
                    <input type="checkbox" id="themeToggle" class="toggle-checkbox">
                </label>
            </div>
        </div>
    </div>

    <div class="pc-info">
        <h2 style="margin-top: 0; margin-bottom: 15px;">📦 Selecciona un juego</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php if (empty($juegos)): ?>
                <p style="color: var(--text-secondary); margin: 0;">Aún no tienes Pokémon en tu colección.</p>
                <a href="coleccion.php" class="btn btn-secondary">➕ Añadir Pokémon</a>
            <?php else: ?>
                <?php foreach ($juegos as $juego): 
                    $juego_traducido = format_game_title($juego);
                    $es_seleccionado = $juego === $juego_seleccionado;
                ?>
                    <a href="juego.php?juego=<?= urlencode($juego) ?>" 
                       class="btn" 
                       style="<?= $es_seleccionado ? 'background: var(--accent-secondary); transform: scale(1.05);' : 'background: var(--accent-tertiary);' ?>">
                        <?= htmlspecialchars($juego_traducido) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($juego_seleccionado): ?>
        <div style="margin-top: 40px;">
            <h2 style="margin-bottom: 20px;">
                📋 PC de <?= htmlspecialchars(format_game_title($juego_seleccionado)) ?>
                <span style="color: var(--text-secondary); font-weight: 400; font-size: 0.9em;">
                    (Página <?= $pagina_actual ?> de <?= $total_paginas ?>, mostrando <?= count($pokemon_por_juego) ?> de <?= $total_pokemon_juego ?>)
                </span>
            </h2>

            <div class="pc-grid">
                <?php 
                    // Mostrar los 30 slots del PC
                    for ($i = 0; $i < 30; $i++): 
                        $pokemon = $pokemon_por_juego[$i] ?? null;
                        $sprite_url = $pokemon ? "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/" . $pokemon['pokedex_numero'] . ".png" : '';
                ?>
                    <div class="pc-slot <?= $pokemon ? 'pc-slot-pokemon' : 'pc-slot-empty' ?>" 
                         <?php if ($pokemon): ?> 
                            title="Lv. <?= htmlspecialchars($pokemon['nivel']) ?> - <?= htmlspecialchars($pokemon['nombre']) ?>"
                         <?php endif; ?>>
                        <?php if ($pokemon): ?>
                            <img src="<?= $sprite_url ?>" class="pc-pokemon-sprite" alt="<?= htmlspecialchars($pokemon['nombre']) ?>">
                            <div class="pc-pokemon-name"><?= htmlspecialchars($pokemon['nombre']) ?></div>
                            <div class="pc-pokemon-level">Lv. <?= htmlspecialchars($pokemon['nivel']) ?></div>
                        <?php else: ?>
                            <span style="font-size: 2em; color: var(--text-secondary);">📭</span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if ($total_paginas > 1): ?>
                <div style="margin-top: 24px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                    <span style="color: var(--text-secondary);">Navegar páginas:</span>
                    <?php if ($pagina_actual > 1): ?>
                        <a href="juego.php?juego=<?= urlencode($juego_seleccionado) ?>&page=<?= $pagina_actual - 1 ?>" class="btn btn-tertiary btn-small">← Anterior</a>
                    <?php endif; ?>

                    <?php for ($pagina = 1; $pagina <= $total_paginas; $pagina++): ?>
                        <a href="juego.php?juego=<?= urlencode($juego_seleccionado) ?>&page=<?= $pagina ?>" 
                           class="btn btn-small" 
                           style="<?= $pagina === $pagina_actual ? 'background: var(--accent-secondary); color: #fff;' : '' ?>">
                            <?= $pagina ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="juego.php?juego=<?= urlencode($juego_seleccionado) ?>&page=<?= $pagina_actual + 1 ?>" class="btn btn-tertiary btn-small">Siguiente →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (count($pokemon_por_juego) > 0): ?>
                <div style="margin-top: 30px; padding: 20px; background: var(--bg-secondary); border-radius: 8px; border: 1px solid var(--border-color);">
                    <h3 style="margin-top: 0; margin-bottom: 15px;">📊 Detalles</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <?php foreach ($pokemon_por_juego as $pkmn): 
                            $ball_formateada = strtolower(str_replace(' ', '-', trim($pkmn['ball'])));
                            $ball_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/{$ball_formateada}.png";
                            $sprite_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/" . $pkmn['pokedex_numero'] . ".png";
                        ?>
                            <div class="card">
                                <div class="card-header" style="display: flex; align-items: center; justify-content: space-between;">
                                    <div>
                                        <div style="font-weight: 600;">Lv. <?= htmlspecialchars($pkmn['nivel']) ?></div>
                                        <div style="font-size: 0.9em; font-weight: 400;">#<?= htmlspecialchars($pkmn['pokedex_numero']) ?></div>
                                    </div>
                                    <img src="<?= $ball_url ?>" style="width: 24px; height: 24px; image-rendering: pixelated;" alt="<?= htmlspecialchars($pkmn['ball']) ?>" onerror="this.src='https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/poke-ball.png'">
                                </div>
                                <div class="card-body">
                                    <div style="text-align: center; margin-bottom: 15px;">
                                        <img src="<?= $sprite_url ?>" style="width: 100px; height: 100px; image-rendering: pixelated;" alt="<?= htmlspecialchars($pkmn['nombre']) ?>">
                                        <div style="font-weight: 600; margin-top: 8px; font-size: 1.1em;"><?= htmlspecialchars($pkmn['nombre']) ?></div>
                                    </div>
                                    
                                    <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9em;">
                                        <div><strong>🧬 Naturaleza:</strong> <?= htmlspecialchars($pkmn['naturaleza']) ?></div>
                                        <div><strong>📍 Ruta:</strong> <?= htmlspecialchars($pkmn['ruta']) ?></div>
                                        <div><strong>🔄 Encuentros:</strong> <?= htmlspecialchars($pkmn['encuentros']) ?></div>
                                        <div><strong>📅 Fecha:</strong> <?= htmlspecialchars($pkmn['fecha']) ?></div>
                                        <div><strong>🕹️ Consola:</strong> <?= htmlspecialchars($pkmn['consola']) ?></div>
                                    </div>
                                </div>
                                <div class="card-footer" style="justify-content: space-between;">
                                    <a href="coleccion.php?accion=editar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn btn-tertiary btn-small">✏️ Editar</a>
                                    <a href="coleccion.php?accion=eliminar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn btn-small" style="background: #d32f2f;" onclick="return confirm('¿Seguro que quieres eliminar este Pokémon?');">🗑️ Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
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
