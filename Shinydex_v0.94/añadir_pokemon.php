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
    
    include "config.php";
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = 
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

    $message = "";

    try 
        {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } 
    catch (\PDOException $e) 
        {
            die("Ha fallado la conexión: " . $e->getMessage());
        }

    // Ahora configuro la recepción de los datos añadidos en la página de añadir Pokémon
    if ($_SERVER["REQUEST_METHOD"] === "POST") 
        {
            $pokedex_numero = !empty($_POST['pokedex_numero']) ? (int) $_POST['pokedex_numero'] : 0;
            $nombre         = trim($_POST['nombre']);
            $nivel          = (int) $_POST['nivel'];
            $naturaleza     = trim($_POST['naturaleza']);
            $ball           = trim($_POST['ball']);
            $ruta           = trim($_POST['ruta']);
            $encuentros     = (int) $_POST['encuentros'];
            $consola        = trim($_POST['consola']);
            $juego          = trim($_POST['juego']);
            $fecha          = trim($_POST['fecha']);
            $user_id        = (int) $_SESSION['user_id'];

            // Por seguridad, se implementa este statement para prevenir ataques por inyección de sql
            $sql = "INSERT INTO pokemon_atrapados (pokedex_numero, nombre, nivel, naturaleza, ball, ruta, encuentros, consola, juego, fecha, usuario_id) 
                    VALUES (:pokedex_numero, :nombre, :nivel, :naturaleza, :ball, :ruta, :encuentros, :consola, :juego, :fecha, :user_id)";
            
            $stmt = $pdo->prepare($sql);
            
            try 
                {
                    $stmt->execute([
                        ':pokedex_numero' => $pokedex_numero,
                        ':nombre'         => $nombre,
                        ':nivel'          => $nivel,
                        ':naturaleza'     => $naturaleza,
                        ':ball'           => $ball,
                        ':ruta'           => $ruta,
                        ':encuentros'     => $encuentros,
                        ':consola'        => $consola,
                        ':juego'          => $juego,
                        ':fecha'          => $fecha,
                        ':user_id'        => $user_id
                    ]);

                    header('Location:coleccion.php');
                } 
            catch (\PDOException $e) 
                {
                    $message = "<div class='error'>Error añadiendo el Pokémon: " . $e->getMessage() . "</div>";
                }
        }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shinydex - Añadir Pokémon</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body data-skip-theme-form-populators="true">
    <div class="header">
        <h1>✨ Añadir Pokémon</h1>
        <div class="header-actions">
            <a href="coleccion.php" class="btn btn-secondary">📚 Mi Colección</a>
            <div class="theme-toggle">
                <label for="themeToggle" style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                    <span>🌙 Modo Oscuro</span>
                    <input type="checkbox" id="themeToggle" class="toggle-checkbox">
                </label>
            </div>
        </div>
    </div>

    <div class="form-container">
        <h2 style="margin-top: 0; text-align: center; margin-bottom: 30px;">¡Añade un nuevo Pokémon Shiny a tu colección!</h2>
        
        <?= $message ?>

        <form method="POST" action="">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="nombre">🔍 Nombre del Pokémon:</label>
                    <input type="text" id="nombre" name="nombre" placeholder="Ej: Pikachu, Mewtwo..." list="pokemon-names" autocomplete="off" required>
                    <datalist id="pokemon-names"></datalist>
                    <span class="api-note">Introduce el nombre y clica fuera para buscar automáticamente su número Pokédex y sprites</span>
                    <input type="hidden" id="pokedex_numero" name="pokedex_numero" value="">
                </div>

                <div id="contenedor-sprite" style="grid-column: 1 / -1; text-align: center; display: none; background: var(--bg-tertiary); padding: 20px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0; color: var(--text-secondary); font-size: 0.9em;">Vista previa del Pokémon:</p>
                    <img id="front-sprite" src="" alt="Front" style="width: 120px; height: 120px; image-rendering: pixelated; margin: 0 10px;">
                    <img id="back-sprite" src="" alt="Back" style="width: 120px; height: 120px; image-rendering: pixelated; margin: 0 10px;">
                </div>

                <div class="form-group">
                    <label for="nivel">📊 Nivel:</label>
                    <input type="number" id="nivel" name="nivel" min="1" max="100" placeholder="1-100" required>
                </div>

                <div class="form-group">
                    <label for="naturaleza">🎯 Naturaleza:</label>
                    <select id="naturaleza" name="naturaleza" required>
                        <option value="">Selecciona una naturaleza</option>
                    </select>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="ball">🔴 Ball usada:</label>
                    <div id="ball-container" style="display: flex; flex-wrap: wrap; gap: 12px; background: var(--bg-tertiary); padding: 15px; border-radius: 8px;"></div>
                    <input type="hidden" id="ball" name="ball" required>
                </div>

                <div class="form-group">
                    <label for="juego">🎮 Versión del juego:</label>
                    <select id="juego" name="juego" required>
                        <option value="">Selecciona una versión</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="consola">🕹️ Consola:</label>
                    <select id="consola" name="consola" required>
                        <option value="">Selecciona una consola</option>
                        <option value="GameBoy">GameBoy</option>
                        <option value="GameBoy Color">GameBoy Color</option>
                        <option value="GameBoy Advance">GameBoy Advance</option>
                        <option value="Gamecube">Gamecube</option>
                        <option value="Nintendo DS">Nintendo DS</option>
                        <option value="Nintendo 3DS">Nintendo 3DS</option>
                        <option value="Nintendo Switch">Nintendo Switch</option>
                        <option value="Nintendo Switch 2">Nintendo Switch 2</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ruta">📍 Ruta:</label>
                    <input type="text" id="ruta" name="ruta" placeholder="Ej: Bosque Verde, Ruta 1..." required>
                </div>

                <div class="form-group">
                    <label for="encuentros">🔄 Número de encuentros:</label>
                    <input type="number" id="encuentros" name="encuentros" min="1" placeholder="Vistos antes de encontrarlo" required>
                </div>

                <div class="form-group">
                    <label for="fecha">📅 Fecha de captura:</label>
                    <input type="date" id="fecha" name="fecha" required>
                </div>
            </div>

            <button type="submit" class="btn btn-secondary" style="width: 100%; margin-top: 30px; font-size: 1.1em; padding: 15px;">
                ➕ Añadir a mi colección
            </button>
        </form>
    </div>

    <script>
        window.skipThemeFormPopulators = true;
    </script>

    <script src="theme.js"></script>

<script>
    // Traducciones de las naturalezas para mostrar en castellano en el select, ya que PokeAPI tiene los nombres en inglés
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

    // Cargar autocompletado de nombres de Pokémon
    fetch('https://pokeapi.co/api/v2/pokemon?limit=2000')
        .then(response => response.json())
        .then(data =>
            {
                const dataList = document.getElementById('pokemon-names');
                const seenPokemon = new Set();
                data.results.forEach(pokemon =>
                    {
                        if (seenPokemon.has(pokemon.name)) return;
                        seenPokemon.add(pokemon.name);

                        const option = document.createElement('option');
                        option.value = pokemon.name.charAt(0).toUpperCase() + pokemon.name.slice(1);
                        dataList.appendChild(option);
                    });
            })
        .catch(() => {
            // El autocompletado puede fallar sin bloquear el formulario
        });

    // Cargar naturalezas
    fetch('https://pokeapi.co/api/v2/nature/?limit=1000')
        .then(response => response.json())
        .then(data => 
            {
                const select = document.getElementById('naturaleza');
                if (!select) {
                    return;
                }
                while (select.options.length > 1) {
                    select.remove(1);
                }

                const seenNatures = new Set();
                const naturals = data.results
                    .map(nature => ({
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
                        select.appendChild(option);
                    });
            });

    // Traducciones de las versiones de los juegos de PokeAPI a español
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
                .then(data => {
                    const items = data[resultsKey] || [];
                    if (data.next) 
                        {
                            return fetchAllPages(data.next, resultsKey)
                                .then(nextItems => items.concat(nextItems));
                        }
                    return items;
                });
        }

    function humanizeText(text) 
        {
            return text.replace(/-/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        }

    // Cargar versiones de los juegos
    fetchAllPages('https://pokeapi.co/api/v2/version/?limit=1000', 'results')
        .then(versions =>
            {
                const select = document.getElementById('juego');
                if (!select) {
                    return;
                }
                while (select.options.length > 1) {
                    select.remove(1);
                }

                const seenVersions = new Set();
                versions.forEach(version =>
                    {
                        const normalizedVersion = version.name.toLowerCase();
                        if (seenVersions.has(normalizedVersion)) return;
                        seenVersions.add(normalizedVersion);

                        if (versionesTraducidas[normalizedVersion]) 
                            {
                                const option = document.createElement('option');
                                const translated = versionesTraducidas[normalizedVersion];
                                option.value = version.name;
                                option.textContent = translated;
                                select.appendChild(option);
                            }
                    });
            });

    //Cargo las balls con sus sprites, y si no tienen sprite, con un botón con su nombre traducido, ya que PokeAPI tiene los nombres en inglés
    fetchAllPages('https://pokeapi.co/api/v2/item-category/34/?limit=1000', 'items')
        .then(items =>
            {
                const container = document.getElementById('ball-container');
                if (!container) {
                    return;
                }
                container.innerHTML = '';

                const seenBalls = new Set();
                items.forEach(item =>
                    {
                        if (seenBalls.has(item.name)) return;
                        seenBalls.add(item.name);

                        const ballButton = document.createElement('button');
                        ballButton.type = 'button';
                        ballButton.className = 'ball-button';
                        ballButton.title = humanizeText(item.name);
                        ballButton.textContent = humanizeText(item.name);
                        ballButton.dataset.ballName = item.name;

                        ballButton.addEventListener('click', () =>
                            {
                                document.getElementById('ball').value = item.name;
                                document.querySelectorAll('#ball-container .ball-button').forEach(i => i.style.border = 'none');
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
                                // Si falla la carga de datos del item, mantenemos el botón con el nombre traducido.
                            });
                    });
            });

    document.getElementById('nombre').addEventListener('blur', function() 
        {
            const nombrePokemon = this.value.toLowerCase().trim();
            const nombreInput = document.getElementById('nombre');
            const dexNumInput = document.getElementById('pokedex_numero');
            const contenedorSprite = document.getElementById('contenedor-sprite');
            const frontSprite = document.getElementById('front-sprite');
            const backSprite = document.getElementById('back-sprite');
            
            if (nombrePokemon) 
                {
                    nombreInput.value = "Cargando...";
                    contenedorSprite.style.display = 'none';

                    // Los datos necesarios de los Pokémon se obtendrán desde la PokeAPI buscando por nombre
                    fetch(`https://pokeapi.co/api/v2/pokemon/${nombrePokemon}`)
                        .then(response => 
                            {
                                if (!response.ok) 
                                    {
                                        throw new Error('Pokémon no encontrado');
                                    }
                                return response.json();
                            })
                        .then(data => 
                            {
                                // Hay que poner la primera letra del Pokémon en mayúscula por si acaso
                                const pokemonNombre = data.name.charAt(0).toUpperCase() + data.name.slice(1);
                                nombreInput.value = pokemonNombre;
                                // Se almacena el número de Pokédex en el campo oculto
                                dexNumInput.value = data.id;

                                // El modelo usado será el utilizado en Pokémon Showdown (modelo 3D ripeado de los juegos) y si no se encuentra, buscará el sprite shiny 2D más moderno asignado o incluso el sprite sin ser shiny más moderno de no encontrar ese tampoco
                                const showdown = data.sprites.other.showdown;
                                
                                const frontUrl = data.sprites.other.showdown.front_shiny || data.sprites.front_shiny || data.sprites.front_default;
                                const backUrl  = data.sprites.other.showdown.back_shiny || data.sprites.back_shiny || data.sprites.back_default;

                                // Se actualizan las imágenes con lo que nos devuelve la PokeAPI
                                if (frontUrl) 
                                    {
                                        frontSprite.src = frontUrl;
                                        frontSprite.style.display = 'inline';
                                    } 
                                else 
                                    {
                                        frontSprite.style.display = 'none';
                                    }

                                if (backUrl) 
                                    {
                                        backSprite.src = backUrl;
                                        backSprite.style.display = 'inline';
                                    } 
                                else 
                                    {
                                        backSprite.style.display = 'none';
                                    }

                                // Se muestran los contenedores si al menos hay un sprite
                                if (frontUrl || backUrl) 
                                    {
                                        contenedorSprite.style.display = 'block';
                                    }
                            })
                        .catch(error => 
                            {
                                nombreInput.value = "";
                                contenedorSprite.style.display = 'none';
                                alert("No se ha podido encontrar un Pokémon con ese nombre");
                            });
                }
        });
</script>
    <footer class="site-footer">
        <p>Recursos utilizados de PokeAPI. Pokémon y todo su contenido son propiedad de Nintendo, Game Freak y The Pokémon Company.</p>
    </footer>

</body>
</html>