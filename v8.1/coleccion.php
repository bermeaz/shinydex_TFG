<?php
    session_start();

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

    $stmt = $pdo->prepare("SELECT * FROM pokemon_atrapados WHERE usuario_id = ? ORDER BY pokedex_numero ASC");
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Colección de <?= htmlspecialchars($usuario_objetivo_nombre) ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f0f0f5; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 15px; background: #e3350d; color: white; text-decoration: none; border-radius: 4px; }
        
        .tabla { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        
        .tarjeta { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); overflow: hidden; position: relative; border: 2px solid #ddd; }
        .tarjeta-header { background: #3b4cca; color: white; padding: 10px; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
        .tarjeta-body { padding: 15px; text-align: center; }
        
        .poke-sprite { width: 120px; height: 120px; image-rendering: pixelated; margin-top: -10px; }
        .ball-icono { width: 24px; height: 24px; image-rendering: pixelated; background: white; border-radius: 50%; padding: 2px; }
        
        .stats { text-align: left; background: #f9f9f9; padding: 10px; border-radius: 8px; font-size: 0.9em; line-height: 1.6; color: #444; }
        .stat-fila { display: flex; align-items: center; gap: 8px; }
        .icono { width: 16px; opacity: 0.6; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Colección de <?= htmlspecialchars($usuario_objetivo_nombre) ?></h1>
        <div>
            <?php if ($es_propietario): ?>
                <a href="añadir_pokemon.php" class="btn">+ Añadir Pokémon</a>
            <?php endif; ?>
            <a href="index.php" class="btn" style="background: #555;">Logout</a>
        </div>
    </div>

    <form method="GET" action="coleccion.php" style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <label style="display: flex; flex: 1 1 300px; gap: 8px; align-items: center;">
            <span>Buscar colección por username:</span>
            <input type="text" name="buscar_user" placeholder="Nombre de usuario" value="<?= htmlspecialchars($busqueda_usuario) ?>" style="flex: 1; padding: 10px; border-radius: 6px; border: 1px solid #ccc;">
        </label>
        <button type="submit" class="btn" style="background: #0073e6;">Buscar</button>
        <?php if ($busqueda_usuario): ?>
            <a href="coleccion.php" class="btn" style="background: #555;">Ver mi colección</a>
        <?php endif; ?>
    </form>

    <?php if ($pokemon_editado || $mensaje_error): ?>
        <div style="max-width: 720px; margin-bottom: 20px; background: #fff; border: 1px solid #ccc; border-radius: 12px; padding: 18px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <?php if ($mensaje_error): ?>
                <div style="color: #721c24; background: #f8d7da; padding: 12px; border-radius: 8px; margin-bottom: 12px;"><?= htmlspecialchars($mensaje_error) ?></div>
            <?php endif; ?>

            <?php if ($pokemon_editado): ?>
                <h2 style="margin-top: 0;">Editar Pokémon: <?= htmlspecialchars($pokemon_editado['nombre']) ?></h2>
                <form method="POST" action="coleccion.php">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($pokemon_editado['id']) ?>">
                    <div style="display: grid; gap: 12px; grid-template-columns: 1fr 1fr;">
                        <label>
                            Nombre del Pokémon:
                            <input type="text" id="edit-nombre" name="nombre" value="<?= htmlspecialchars($pokemon_editado['nombre']) ?>" required>
                            <span style="font-size: 0.85em; color: #666; display: block; margin-top: 4px;">Clica fuera para buscar automáticamente el número</span>
                        </label>
                        <label>
                            Número de la Pokédex Nacional:
                            <input type="number" id="edit-pokedex" name="pokedex_numero" value="<?= htmlspecialchars($pokemon_editado['pokedex_numero']) ?>" min="1" max="1025" required>
                        </label>
                        <label>
                            Nivel:
                            <input type="number" id="edit-nivel" name="nivel" value="<?= htmlspecialchars($pokemon_editado['nivel']) ?>" min="1" max="100" required>
                        </label>
                        <label>
                            Naturaleza:
                            <select id="edit-naturaleza" name="naturaleza">
                                <option value="">Selecciona una naturaleza</option>
                            </select>
                        </label>
                        <label>
                            Ball usada:
                            <div id="edit-ball-container" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
                            <input type="hidden" id="edit-ball" name="ball">
                        </label>
                        <label>
                            Ruta:
                            <input type="text" id="edit-ruta" name="ruta" value="<?= htmlspecialchars($pokemon_editado['ruta']) ?>">
                        </label>
                        <label>
                            Encuentros:
                            <input type="number" id="edit-encuentros" name="encuentros" value="<?= htmlspecialchars($pokemon_editado['encuentros']) ?>" min="0">
                        </label>
                        <label>
                            Consola:
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
                        </label>
                        <label>
                            Juego:
                            <select id="edit-juego" name="juego">
                                <option value="">Selecciona una versión</option>
                            </select>
                        </label>
                        <label style="grid-column: span 2;">
                            Fecha de captura:
                            <input type="date" id="edit-fecha" name="fecha" value="<?= htmlspecialchars($pokemon_editado['fecha']) ?>" required>
                        </label>
                    </div>
                    <button type="submit" style="margin-top: 16px; padding: 10px 18px; background: #3b4cca; color: white; border: none; border-radius: 8px; cursor: pointer;">Guardar cambios</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="tabla">
        <?php 
            foreach ($lista_pokemon as $pkmn): 
            // Cambio el nombre de la ball porque está en distinto formato en la API y queremos buscar su icono ("Ultra Ball" hay que cambiarlo a "ultra-ball")
            $ball_formateada = strtolower(str_replace(' ', '-', trim($pkmn['ball'])));
            $ball_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/{$ball_formateada}.png";
            
            // Cambio también la URL de los sprites para que sea más eficiente y rápido
            $sprite_url = "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/shiny/" . $pkmn['pokedex_numero'] . ".png";
        ?>
            <div class="tarjeta">
                <div class="tarjeta-header">
                    <span>Nivel <?= htmlspecialchars($pkmn['nivel']) ?></span>
                    <span><?= htmlspecialchars($pkmn['nombre']) ?></span>
                    <img src="<?= $ball_url ?>" class="ball-icono" alt="<?= htmlspecialchars($pkmn['ball']) ?>" onerror="this.src='https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/items/poke-ball.png'">
                <!-- Si da error al poner el icono de la ball, lo pongo por defecto como una Poké Ball normal -->
                </div>
                
                <div class="tarjeta-body">
                    <img src="<?= $sprite_url ?>" class="poke-sprite" alt="<?= htmlspecialchars($pkmn['nombre']) ?>">
                    
                    <div class="stats">
                        <div class="stat-fila">
                            <strong>🧬 Naturaleza:</strong> <?= htmlspecialchars($pkmn['naturaleza']) ?>
                        </div>
                        <div class="stat-fila">
                            <strong>📍 Atrapado en:</strong> <?= htmlspecialchars($pkmn['ruta']) ?>
                        </div>
                        <div class="stat-fila">
                            <strong>🔁 Encuentros:</strong> <?= htmlspecialchars($pkmn['encuentros']) ?>
                        </div>
                        <div class="stat-fila">
                            <strong>El:</strong> <?= htmlspecialchars($pkmn['fecha']) ?>
                        </div>
                        <div class="stat-fila" style="margin-top: 5px; font-size: 0.85em; color: #888;">
                            🎮 <?= htmlspecialchars($versionesTraducidas[strtolower(trim($pkmn['juego']))] ?? ucfirst(strtolower($pkmn['juego']))) ?> (<?= htmlspecialchars($pkmn['consola']) ?>)
                        </div>
                    </div>
                    <?php if ($es_propietario): ?>
                        <div style="padding: 15px; text-align: center; border-top: 1px solid #eee; background: #fafafa; display: flex; justify-content: center; gap: 10px;">
                            <a href="coleccion.php?accion=editar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn" style="background: #0073e6;">Editar</a>
                            <a href="coleccion.php?accion=eliminar&id=<?= htmlspecialchars($pkmn['id']) ?>" class="btn" style="background: #d32f2f;" onclick="return confirm('¿Seguro que quieres eliminar este Pokémon?');">Eliminar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php 
            endforeach; 
        ?>
        
        <?php 
            if(empty($lista_pokemon)): 
        ?>
            <p>No hay ningún Pokémon en tu colección. ¡Empieza tu camino para atraparlos a todos!.</p>
        <?php 
            endif; 
        ?>
    </div>

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
            fetch('https://pokeapi.co/api/v2/nature/')
                .then(response => response.json())
                .then(data => 
                    {
                        const select = document.getElementById('edit-naturaleza');
                        data.results.forEach(nature => 
                            {
                                const option = document.createElement('option');
                                option.value = naturalezasTraducidas[nature.name] || nature.name;
                                option.textContent = naturalezasTraducidas[nature.name] || nature.name;
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
                        items.forEach(item => 
                            {
                                fetch(item.url)
                                    .then(res => res.json())
                                    .then(itemData => 
                                        {
                                            const spriteUrl = itemData.sprites?.default;
                                            const ballElement = spriteUrl ? document.createElement('img') : document.createElement('button');

                                            if (spriteUrl) 
                                                {
                                                    ballElement.src = spriteUrl;
                                                    ballElement.alt = itemData.name;
                                                    ballElement.style.width = '32px';
                                                    ballElement.style.height = '32px';
                                                }
                                            else 
                                                {
                                                    ballElement.textContent = humanizeText(itemData.name);
                                                    ballElement.style.padding = '6px 10px';
                                                    ballElement.style.fontSize = '0.8rem';
                                                    ballElement.style.minWidth = '80px';
                                                    ballElement.style.minHeight = '32px';
                                                    ballElement.style.background = '#f0f0f0';
                                                    ballElement.style.border = '1px solid #ccc';
                                                    ballElement.style.borderRadius = '4px';
                                                }

                                            ballElement.style.cursor = 'pointer';
                                            ballElement.style.border = 'none';

                                            if (itemData.name === currentBall) {
                                                ballElement.style.border = '2px solid red';
                                            }

                                            ballElement.onclick = () => 
                                                {
                                                    if (editBallInput) editBallInput.value = itemData.name;
                                                    document.querySelectorAll('#edit-ball-container img, #edit-ball-container button').forEach(i => i.style.border = 'none');
                                                    ballElement.style.border = '2px solid red';
                                                };
                                            container.appendChild(ballElement);
                                        });
                            });
                    });
        }

    if (document.getElementById('edit-juego')) 
        {
            const juegoActual = <?= json_encode($pokemon_editado['juego'] ?? '') ?>;
            fetchAllPages('https://pokeapi.co/api/v2/version/?limit=1000', 'results')
                .then(versions => 
                    {
                        const select = document.getElementById('edit-juego');
                        versions.forEach(version => 
                            {
                                const normalizedVersion = version.name.toLowerCase();
                                if (versionesTraducidas[normalizedVersion]) 
                                    {
                                        const translated = versionesTraducidas[normalizedVersion];
                                        const option = document.createElement('option');
                                        option.value = translated;
                                        option.textContent = translated;

                                        const juegoNormalizado = juegoActual.toLowerCase();
                                        if (juegoActual === translated || juegoNormalizado === normalizedVersion || juegoNormalizado.replace(/\s+/g, '-') === normalizedVersion) {
                                            option.selected = true;
                                        }

                                        select.appendChild(option);
                                    }
                            });
                    });
        }
</script>

</body>
</html>