<?php
    session_start();

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
    <title>Shinydex</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 500px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin: auto; }
        h2 { text-align: center; color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #e3350d; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
        button:hover { background-color: #c02c0b; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb;}
        .error { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb;}
        .api-note { font-size: 0.85em; color: #666; margin-top: -5px; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>

<div class="container">
    <h2>¡Añade un Pokémon a tu colección!</h2>
    
    <?= $message ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="nombre">Nombre del Pokémon:</label>
            <input type="text" id="nombre" name="nombre" required>
            <span class="api-note">Introduce el nombre y clica fuera de la caja para buscar automáticamente su número de Pokédex y su modelo</span>
            <input type="hidden" id="pokedex_numero" name="pokedex_numero" value="">
        </div>

        <div id="contenedor-sprite" style="text-align: center; margin-bottom: 15px; display: none;">
            <img id="front-sprite" src="" alt="Front" style="width: 96px; height: 96px; image-rendering: pixelated;">
            <img id="back-sprite" src="" alt="Back" style="width: 96px; height: 96px; image-rendering: pixelated;">
        </div>

        <div class="form-group">
            <label for="nivel">Nivel:</label>
            <input type="number" id="nivel" name="nivel" min="1" max="100" required>
        </div>

        <div class="form-group">
            <label for="naturaleza">Naturaleza:</label>
            <select id="naturaleza" name="naturaleza" required>
                <option value="">Selecciona una naturaleza</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ball">Ball usada:</label>
            <div id="ball-container" style="display: flex; flex-wrap: wrap; gap: 10px;"></div>
            <input type="hidden" id="ball" name="ball" required>
        </div>

        <div class="form-group">
            <label for="juego">Versión del juego:</label>
            <select id="juego" name="juego" required>
                <option value="">Selecciona una versión</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ruta">Ruta:</label>
            <input type="text" id="ruta" name="ruta" required>
        </div>

        <div class="form-group">
            <label for="encuentros">Número de encuentros:</label>
            <input type="number" id="encuentros" name="encuentros" min="1" required>
        </div>

        <div class="form-group">
            <label for="consola">Consola:</label>
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
            <label for="fecha">Fecha de captura:</label>
            <input type="date" id="fecha" name="fecha" required>
        </div>

        <button type="submit">Añadir a la colección</button>
    </form>
</div>

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

    // Cargar naturalezas
    fetch('https://pokeapi.co/api/v2/nature/')
        .then(response => response.json())
        .then(data => 
            {
                const select = document.getElementById('naturaleza');
                data.results.forEach(nature => 
                    {
                        const option = document.createElement('option');
                        option.value = naturalezasTraducidas[nature.name] || nature.name;
                        option.textContent = naturalezasTraducidas[nature.name] || nature.name;
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
                versions.forEach(version =>
                    {
                        if (versionesTraducidas[version.name]) 
                            {
                                const option = document.createElement('option');
                                const translated = versionesTraducidas[version.name];
                                option.value = translated;
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
                                    ballElement.onclick = () =>
                                        {
                                            document.getElementById('ball').value = itemData.name;
                                            document.querySelectorAll('#ball-container img, #ball-container button').forEach(i => i.style.border = 'none');
                                            ballElement.style.border = '2px solid red';
                                        };

                                    container.appendChild(ballElement);
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

</body>
</html>