<?php
    session_set_cookie_params(['path' => '/', 'httponly' => true]);
    session_start();

    if (isset($_SESSION['remember_me']) && $_SESSION['remember_me']) {
        setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/', '', false, true);
    }

    // Si no entras a esta página logeado, te devuelve al login automáticamente
    if (!isset($_SESSION['user_id'])) 
        {
            header("Location: login_registro.php");
            exit;
        }

    // Configuración de la BD
    include "config.php";
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $mensaje_error = "";
    $pokemon_editado = null;

    $naturalezasTraducidas = [
        'hardy' => 'Fuerte',
        'lonely' => 'Huraña',
        'brave' => 'Audaz',
        'adamant' => 'Firme',
        'naughty' => 'Pícara',
        'bold' => 'Osada',
        'docile' => 'Dócil',
        'relaxed' => 'Plácida',
        'impish' => 'Agitada',
        'lax' => 'Floja',
        'timid' => 'Miedosa',
        'hasty' => 'Activa',
        'serious' => 'Seria',
        'jolly' => 'Alegre',
        'naive' => 'Ingenua',
        'modest' => 'Modesta',
        'mild' => 'Afable',
        'quiet' => 'Mansa',
        'bashful' => 'Tímida',
        'rash' => 'Alocada',
        'calm' => 'Serena',
        'gentle' => 'Amable',
        'sassy' => 'Grosera',
        'careful' => 'Cauta',
        'quirky' => 'Rara',
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') 
        {
            $id = (int) $_POST['id'];
            $pokedex_numero = (int) $_POST['pokedex_numero'];
            $nombre = trim($_POST['nombre']);
            $nivel = (int) $_POST['nivel'];
            $naturaleza = trim($_POST['naturaleza']);
            $ball = trim($_POST['ball']);
            $ruta = trim($_POST['ruta']);
            $encuentros = (int) $_POST['encuentros'];
            $consola = trim($_POST['consola']);
            $juego = trim($_POST['juego']);
            $fecha = trim($_POST['fecha']);

            $sql = "UPDATE pokemon_atrapados SET pokedex_numero = :pokedex_numero, nombre = :nombre, nivel = :nivel,
                    naturaleza = :naturaleza, ball = :ball, ruta = :ruta, encuentros = :encuentros, consola = :consola,
                    juego = :juego, fecha = :fecha WHERE id = :id AND usuario_id = :usuario_id";

            $stmt = $pdo->prepare($sql);
            try 
                {
                    $stmt->execute([
                        ':pokedex_numero' => $pokedex_numero,
                        ':nombre' => $nombre,
                        ':nivel' => $nivel,
                        ':naturaleza' => $naturaleza,
                        ':ball' => $ball,
                        ':ruta' => $ruta,
                        ':encuentros' => $encuentros,
                        ':consola' => $consola,
                        ':juego' => $juego,
                        ':fecha' => $fecha,
                        ':id' => $id,
                        ':usuario_id' => $_SESSION['user_id'],
                    ]);

                    header('Location: coleccion.php');
                    exit;
                } 
            catch (PDOException $e) 
                {
                    $mensaje_error = "No se pudo actualizar el Pokémon: " . $e->getMessage();
                }
        }

    if (isset($_GET['accion']) && $_GET['accion'] === 'editar' && isset($_GET['id'])) 
        {
            $id = (int) $_GET['id'];
            $stmt = $pdo->prepare("SELECT * FROM pokemon_atrapados WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $_SESSION['user_id']]);
            $pokemon_editado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pokemon_editado) 
                {
                    $mensaje_error = "No se encontró ese Pokémon o no tienes permiso para editarlo.";
                }
        }

    if (isset($_GET['accion']) && $_GET['accion'] === 'eliminar' && isset($_GET['id'])) 
        {
            $id = (int) $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM pokemon_atrapados WHERE id = ? AND usuario_id = ?");
            try 
                {
                    $stmt->execute([$id, $_SESSION['user_id']]);
                    header('Location: coleccion.php');
                    exit;
                } 
            catch (PDOException $e) 
            {
                    $mensaje_error = "No se pudo eliminar el Pokémon: " . $e->getMessage();
                }
        }

    // Se buscan los Pokémon del usuario
    $busqueda_usuario = '';
    $usuario_objetivo_id = $_SESSION['user_id'];
    $usuario_objetivo_nombre = $_SESSION['username'];
    $es_propietario = true;

    if (isset($_GET['buscar_user']) && trim($_GET['buscar_user']) !== '') 
        {
            $busqueda_usuario = trim($_GET['buscar_user']);
            $stmt = $pdo->prepare("SELECT id, username FROM usuarios WHERE username = ?");
            $stmt->execute([$busqueda_usuario]);
            $usuario_buscado = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario_buscado) 
                {
                    $usuario_objetivo_id = $usuario_buscado['id'];
                    $usuario_objetivo_nombre = $usuario_buscado['username'];
                    $es_propietario = $usuario_objetivo_id === $_SESSION['user_id'];
                } 
            else 
                {
                    $mensaje_error = "No se encontró ningún usuario con ese nombre.";
                }
        }

    $orden = 'pokedex_numero';
    if (isset($_GET['orden']) && in_array($_GET['orden'], ['pokedex_numero', 'encuentros', 'nombre'], true)) 
        {
            $orden = $_GET['orden'];
        }

    $orden_sql_map = [
        'pokedex_numero' => 'pokedex_numero ASC',
        'encuentros' => 'encuentros ASC',
        'nombre' => 'nombre ASC'
    ];
    $orden_sql = $orden_sql_map[$orden];

    $stmt = $pdo->prepare("SELECT * FROM pokemon_atrapados WHERE usuario_id = ? ORDER BY $orden_sql");
    $stmt->execute([$usuario_objetivo_id]);
    $lista_pokemon = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $versionesTraducidas = 
        [
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShinyDex - Colección de <?= htmlspecialchars($usuario_objetivo_nombre) ?></title>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-skip-theme-form-populators="true">
    <div class="header">
        <div>
            <h1>📚 Colección de <?= htmlspecialchars($usuario_objetivo_nombre) ?></h1>
            <p style="color: var(--text-secondary); margin: 5px 0 0 0; font-size: 0.95em;">
                <?php 
                    $totalPokemon = count($lista_pokemon);
                    echo $totalPokemon . ' Pokémon ' . ($totalPokemon === 1 ? 'capturado' : 'capturados');
                ?>
            </p>
        </div>
        <div class="header-actions">
            <?php if ($es_propietario): ?>
                <a href="añadir_pokemon.php" class="btn btn-secondary">➕ Añadir Pokémon</a>
                <a href="juego.php" class="btn btn-secondary">🎮 Ver por Juego</a>
                <a href="estadisticas.php" class="btn btn-secondary">📈 Estadísticas</a>
            <?php endif; ?>
            <a href="index.php?logout=1" class="btn btn-gray">🚪 Logout</a>
            <div class="theme-toggle">
                <label for="themeToggle" style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                    <span>🌙 Modo Oscuro</span>
                    <input type="checkbox" id="themeToggle" class="toggle-checkbox">
                </label>
            </div>
        </div>
    </div>

    <div class="search-form">
        <label>
            <span>🔍 Buscar colección:</span>
            <form method="GET" action="coleccion.php" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap; align-items: center;">
                <input type="text" name="buscar_user" placeholder="Introduce un nombre de usuario" value="<?= htmlspecialchars($busqueda_usuario) ?>">
                <select name="orden" style="min-width: 210px;">
                    <option value="pokedex_numero" <?= $orden === 'pokedex_numero' ? 'selected' : '' ?>>Ordenar por Número de Pokédex</option>
                    <option value="encuentros" <?= $orden === 'encuentros' ? 'selected' : '' ?>>Ordenar por Número de encuentros</option>
                    <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Ordenar por Orden alfabético</option>
                </select>
                <button type="submit" class="btn btn-tertiary">Buscar</button>
                <?php if ($busqueda_usuario): ?>
                    <a href="coleccion.php" class="btn btn-gray">Mi colección</a>
                <?php endif; ?>
            </form>
        </label>
    </div>

    <?php if ($mensaje_error): ?>
        <div class="message error" style="max-width: 1000px; margin: 20px auto;">
            <?= htmlspecialchars($mensaje_error) ?>
        </div>
    <?php endif; ?>

    <?php if ($pokemon_editado): ?>
        <div class="form-container" style="max-width: 900px;">
            <h2 style="margin-top: 0;">✏️ Editar Pokémon: <span style="color: var(--accent-secondary);"><?= htmlspecialchars($pokemon_editado['nombre']) ?></span></h2>
            <form method="POST" action="coleccion.php">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" value="<?= htmlspecialchars($pokemon_editado['id']) ?>">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="edit-nombre">📝 Nombre del Pokémon:</label>
                        <input type="text" id="edit-nombre" name="nombre" value="<?= htmlspecialchars($pokemon_editado['nombre']) ?>" required>
                        <span class="api-note">Clica fuera para buscar automáticamente el número</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-pokedex">🔢 Número Pokédex Nacional:</label>
                        <input type="number" id="edit-pokedex" name="pokedex_numero" value="<?= htmlspecialchars($pokemon_editado['pokedex_numero']) ?>" min="1" max="15000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-nivel">📊 Nivel:</label>
                        <input type="number" id="edit-nivel" name="nivel" value="<?= htmlspecialchars($pokemon_editado['nivel']) ?>" min="1" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-naturaleza">🎯 Naturaleza:</label>
                        <select id="edit-naturaleza" name="naturaleza">
                            <option value="">Selecciona una naturaleza</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="grid-column: 1 / -1;">
                        <label for="edit-ball">🔴 Ball usada:</label>
                        <div id="edit-ball-container" style="display: flex; flex-wrap: wrap; gap: 12px; background: var(--bg-tertiary); padding: 15px; border-radius: 8px;"></div>
                        <input type="hidden" id="edit-ball" name="ball">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-ruta">📍 Ruta:</label>
                        <input type="text" id="edit-ruta" name="ruta" value="<?= htmlspecialchars($pokemon_editado['ruta']) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-encuentros">🔄 Encuentros:</label>
                        <input type="number" id="edit-encuentros" name="encuentros" value="<?= htmlspecialchars($pokemon_editado['encuentros']) ?>" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-consola">🕹️ Consola:</label>
                        <select id="edit-consola" name="consola">
                            <option value="">Selecciona una consola</option>
                            <option value="GameBoy" <?= ($pokemon_editado['consola'] ?? '') === 'GameBoy' ? 'selected' : '' ?>>GameBoy</option>
                            <option value="GameBoy Color" <?= ($pokemon_editado['consola'] ?? '') === 'GameBoy Color' ? 'selected' : '' ?>>GameBoy Color</option>
                            <option value="GameBoy Advance" <?= ($pokemon_editado['consola'] ?? '') === 'GameBoy Advance' ? 'selected' : '' ?>>GameBoy Advance</option>
                            <option value="Gamecube" <?= ($pokemon_editado['consola'] ?? '') === 'Gamecube' ? 'selected' : '' ?>>Gamecube</option>
                            <option value="Nintendo DS" <?= ($pokemon_editado['consola'] ?? '') === 'Nintendo DS' ? 'selected' : '' ?>>Nintendo DS</option>
                            <option value="Nintendo 3DS" <?= ($pokemon_editado['consola'] ?? '') === 'Nintendo 3DS' ? 'selected' : '' ?>>Nintendo 3DS</option>
                            <option value="Nintendo Switch" <?= ($pokemon_editado['consola'] ?? '') === 'Nintendo Switch' ? 'selected' : '' ?>>Nintendo Switch</option>
                            <option value="Nintendo Switch 2" <?= ($pokemon_editado['consola'] ?? '') === 'Nintendo Switch 2' ? 'selected' : '' ?>>Nintendo Switch 2</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-juego">🎮 Versión del juego:</label>
                        <select id="edit-juego" name="juego">
                            <option value="">Selecciona una versión</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-fecha">📅 Fecha de captura:</label>
                        <input type="date" id="edit-fecha" name="fecha" value="<?= htmlspecialchars($pokemon_editado['fecha']) ?>" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 30px; font-size: 1em; padding: 12px;">
                    💾 Guardar cambios
                </button>
            </form>
        </div>
    <?php endif; ?>

    <div style="margin-top: 40px;">
        <?php if (empty($lista_pokemon)): ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 3em; margin-bottom: 20px;">🎮</div>
                <h2 style="color: var(--text-secondary); margin-bottom: 10px;">No hay Pokémon en la colección</h2>
                <p style="color: var(--text-secondary); margin-bottom: 20px;">
                    <?php if ($es_propietario): ?>
                        ¡Empieza tu camino para atrapar los Pokémon shiny más increíbles!
                    <?php else: ?>
                        <?= htmlspecialchars($usuario_objetivo_nombre) ?> aún no ha capturado ningún Pokémon.
                    <?php endif; ?>
                </p>
                <?php if ($es_propietario): ?>
                    <a href="añadir_pokemon.php" class="btn btn-secondary">➕ Añadir tu primer Pokémon</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($lista_pokemon as $pkmn): 
                    $ball_formateada = strtolower(str_replace(' ', '-', trim($pkmn['ball'])));
                    $ball_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/{$ball_formateada}.png";
                    $sprite_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/" . $pkmn['pokedex_numero'] . ".png";
                ?>
                    <div class="pokemon-card">
                        <div class="pokemon-card-header">
                            <span>Lv. <?= htmlspecialchars($pkmn['nivel']) ?></span>
                            <span><?= htmlspecialchars($pkmn['nombre']) ?></span>
                            <img src="<?= $ball_url ?>" class="ball-icon" alt="<?= htmlspecialchars($pkmn['ball']) ?>" onerror="this.src='https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/poke-ball.png'">
                        </div>
                        
                        <div class="pokemon-card-body">
                            <img src="<?= $sprite_url ?>" class="pokemon-sprite" alt="<?= htmlspecialchars($pkmn['nombre']) ?>">
                            
                            <div class="pokemon-stats">
                                <div class="pokemon-stat">
                                    <span class="stat-label">🧬 Naturaleza:</span>
                                    <span class="stat-value"><?= htmlspecialchars($naturalezasTraducidas[$pkmn['naturaleza']] ?? ucwords(str_replace('-', ' ', $pkmn['naturaleza']))) ?></span>
                                </div>
                                <div class="pokemon-stat">
                                    <span class="stat-label">📍 Ruta:</span>
                                    <span class="stat-value"><?= htmlspecialchars($pkmn['ruta']) ?></span>
                                </div>
                                <div class="pokemon-stat">
                                    <span class="stat-label">🔄 Encuentros:</span>
                                    <span class="stat-value"><?= htmlspecialchars($pkmn['encuentros']) ?></span>
                                </div>
                                <div class="pokemon-stat">
                                    <span class="stat-label">📅 Fecha:</span>
                                    <span class="stat-value"><?= htmlspecialchars($pkmn['fecha']) ?></span>
                                </div>
                                <div class="pokemon-stat" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-color);">
                                    <span class="stat-label">🎮 Juego:</span>
                                    <span class="stat-value" style="font-size: 0.9em;">
                                        <?= htmlspecialchars($versionesTraducidas[strtolower(trim($pkmn['juego']))] ?? ucfirst(strtolower($pkmn['juego']))) ?>
                                        <br><span style="font-size: 0.85em; color: var(--text-secondary);"><?= htmlspecialchars($pkmn['consola']) ?></span>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($es_propietario): ?>
                                <div class="pokemon-actions">
                                    <a href="coleccion.php?accion=editar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn btn-tertiary btn-small">✏️ Editar</a>
                                    <a href="coleccion.php?accion=eliminar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn" style="background: #d32f2f;" onclick="return confirm('¿Seguro que quieres eliminar este Pokémon?');">🗑️ Eliminar</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        window.skipThemeFormPopulators = true;
    </script>

    <script src="theme.js"></script>

<script>
    // Script para búsqueda automática en el formulario de edición
    if (document.getElementById('edit-nombre')) {
        document.getElementById('edit-nombre').addEventListener('blur', function() {
            const nombrePokemon = this.value.toLowerCase().trim();
            const dexInput = document.getElementById('edit-pokedex');
            
            if (nombrePokemon) {
                fetch(`https://pokeapi.co/api/v2/pokemon/${nombrePokemon}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Pokémon no encontrado');
                        }
                        return response.json();
                    })
                    .then(data => {
                        dexInput.value = data.id;
                    })
                    .catch(error => {
                        alert("No se ha podido encontrar un Pokémon con ese nombre");
                    });
            }
        });
    }

    // Para que salgan los desplegables de añadir_pokemon.php en la opción de editar
    const naturalezasTraducidas = 
        {
            'hardy': 'Fuerte',
            'lonely': 'Huraña',
            'brave': 'Audaz',
            'adamant': 'Firme',
            'naughty': 'Pícara',
            'bold': 'Osada',
            'docile': 'Dócil',
            'relaxed': 'Plácida',
            'impish': 'Agitada',
            'lax': 'Floja',
            'timid': 'Miedosa',
            'hasty': 'Activa',
            'serious': 'Seria',
            'jolly': 'Alegre',
            'naive': 'Ingenua',
            'modest': 'Modesta',
            'mild': 'Afable',
            'quiet': 'Mansa',
            'bashful': 'Tímida',
            'rash': 'Alocada',
            'calm': 'Serena',
            'gentle': 'Amable',
            'sassy': 'Grosera',
            'careful': 'Cauta',
            'quirky': 'Rara'
        };

    const versionesTraducidas = 
        {
            'red': 'Rojo',
            'blue': 'Azul',
            'yellow': 'Amarillo',
            'gold': 'Oro',
            'silver': 'Plata',
            'crystal': 'Cristal',
            'ruby': 'Rubí',
            'sapphire': 'Zafiro',
            'emerald': 'Esmeralda',
            'firered': 'Rojo Fuego',
            'leafgreen': 'Verde Hoja',
            'diamond': 'Diamante',
            'pearl': 'Perla',
            'platinum': 'Platino',
            'heartgold': 'HeartGold',
            'soulsilver': 'SoulSilver',
            'black': 'Negro',
            'white': 'Blanco',
            'black-2': 'Negro 2',
            'white-2': 'Blanco 2',
            'x': 'X',
            'y': 'Y',
            'omega-ruby': 'Rubí Omega',
            'alpha-sapphire': 'Zafiro Alfa',
            'sun': 'Sol',
            'moon': 'Luna',
            'ultra-sun': 'Ultra Sol',
            'ultra-moon': 'Ultra Luna',
            'lets-go-pikachu': 'Let\'s Go Pikachu',
            'lets-go-eevee': 'Let\'s Go Eevee',
            'sword': 'Espada',
            'shield': 'Escudo',
            'brilliant-diamond': 'Diamante Brillante',
            'shining-pearl': 'Perla Reluciente',
            'legends-arceus': 'Leyendas: Arceus',
            'scarlet': 'Escarlata',
            'violet': 'Púrpura',
            'legends-za': 'Leyendas: ZA'
        };

    function fetchAllPages(url, resultsKey) 
        {
            return fetch(url)
                .then(response => response.json())
                .then(data => 
                    {
                        const items = data[resultsKey] || [];
                        if (data.next) 
                            {
                                return fetchAllPages(data.next, resultsKey).then(nextItems => items.concat(nextItems));
                            }
                        return items;
                    });
        }

    function humanizeText(text) 
        {
            return text.replace(/-/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        }

    if (document.getElementById('edit-naturaleza')) 
        {
            fetch('https://pokeapi.co/api/v2/nature/?limit=1000')
                .then(response => response.json())
                .then(data => 
                    {
                        const select = document.getElementById('edit-naturaleza');
                        if (!select || select.options.length > 1) {
                            return;
                        }

                        const seenNatures = new Set();
                        const naturals = data.results.map(nature =>
                            ({
                                value: nature.name,
                                label: naturalezasTraducidas[nature.name] || humanizeText(nature.name)
                            }))
                            .filter(nature => {
                                if (seenNatures.has(nature.value)) {
                                    return false;
                                }
                                seenNatures.add(nature.value);
                                return true;
                            });

                        naturals.sort((a, b) => a.label.localeCompare(b.label, 'es'));
                        naturals.forEach(nature => 
                            {
                                const option = document.createElement('option');
                                option.value = nature.value;
                                option.textContent = nature.label;
                                if (option.value === '<?= htmlspecialchars($pokemon_editado['naturaleza'] ?? '') ?>') option.selected = true;
                                select.appendChild(option);
                            });
                    });
        }

    if (document.getElementById('edit-ball-container')) 
        {
            const currentBall = '<?= htmlspecialchars($pokemon_editado['ball'] ?? '') ?>';
            const editBallInput = document.getElementById('edit-ball');
            if (editBallInput) editBallInput.value = currentBall;

            fetchAllPages('https://pokeapi.co/api/v2/item-category/34/?limit=1000', 'items')
                .then(items => 
                    {
                        const container = document.getElementById('edit-ball-container');
                        if (!container) {
                            return;
                        }
                        container.innerHTML = '';

                        const seenBalls = new Set();
                        items.forEach(item => 
                            {
                                if (seenBalls.has(item.name)) {
                                    return;
                                }
                                seenBalls.add(item.name);

                                const ballButton = document.createElement('button');
                                ballButton.type = 'button';
                                ballButton.className = 'ball-button';
                                ballButton.title = humanizeText(item.name);
                                ballButton.textContent = humanizeText(item.name);
                                ballButton.dataset.ballName = item.name;

                                if (item.name === currentBall) {
                                    ballButton.style.border = '2px solid red';
                                }

                                ballButton.addEventListener('click', () => 
                                    {
                                        if (editBallInput) editBallInput.value = item.name;
                                        document.querySelectorAll('#edit-ball-container .ball-button').forEach(i => i.style.border = 'none');
                                        ballButton.style.border = '2px solid red';
                                    });

                                container.appendChild(ballButton);

                                fetch(item.url)
                                    .then(res => res.json())
                                    .then(itemData => 
                                        {
                                            const spriteUrl = itemData.sprites?.default;
                                            if (spriteUrl) 
                                                {
                                                    ballButton.textContent = '';
                                                    const img = document.createElement('img');
                                                    img.src = spriteUrl;
                                                    img.alt = itemData.name;
                                                    img.className = 'ball-icon-select';
                                                    ballButton.appendChild(img);
                                                }
                                        })
                                    .catch(() => {
                                        // Mantener el nombre si falla la carga de datos del item.
                                    });
                            });
                    });
        }

    if (document.getElementById('edit-juego')) 
        {
            const juegoActual = <?= json_encode($pokemon_editado['juego'] ?? '') ?>;
            const juegoNormalizado = juegoActual.toString().toLowerCase().trim();
            const seenVersions = new Set();
            fetchAllPages('https://pokeapi.co/api/v2/version/?limit=1000', 'results')
                .then(versions => 
                    {
                        const select = document.getElementById('edit-juego');
                        if (!select || select.options.length > 1) {
                            return;
                        }

                        versions.forEach(version => 
                            {
                                const normalizedVersion = version.name.toLowerCase();
                                if (seenVersions.has(normalizedVersion) || !versionesTraducidas[normalizedVersion]) {
                                    return;
                                }
                                seenVersions.add(normalizedVersion);

                                const translated = versionesTraducidas[normalizedVersion];
                                const option = document.createElement('option');
                                option.value = version.name;
                                option.textContent = translated;

                                if (
                                    juegoActual === version.name ||
                                    juegoActual === translated ||
                                    juegoNormalizado === normalizedVersion ||
                                    juegoNormalizado.replace(/\s+/g, '-') === normalizedVersion
                                ) {
                                    option.selected = true;
                                }

                                select.appendChild(option);
                            });
                    });
        }
</script>
    <footer class="site-footer">
        <div class="footer-inner">
            <p class="footer-text">Recursos utilizados de PokeAPI. Pokémon y todo su contenido son propiedad de Nintendo, Game Freak y The Pokémon Company.</p>
            <img class="footer-logo" src="https://raw.githubusercontent.com/PokeAPI/media/master/logo/pokeapi_256.png" alt="PokeAPI Logo">
        </div>
    </footer>
</body>
</html>