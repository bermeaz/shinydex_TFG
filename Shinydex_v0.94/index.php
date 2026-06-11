<?php
session_set_cookie_params(['path' => '/', 'httponly' => true]);
session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/', '', false, true);
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: coleccion.php');
    exit;
}

// Configuración de la BD
include "config.php";
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$message = "";

// Dependiendo de si es un registro o un login, se hará una cosa u otra
if ($_SERVER["REQUEST_METHOD"] === "POST") 
    {
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $action = $_POST['action']; // Action será "login" o "registro"

        if ($action === 'registro') 
            {
                if (!isset($_POST['repeat_password']) || empty($_POST['repeat_password'])) 
                    {
                    $message = "<div class='error'>Debes repetir la contraseña para registrarte.</div>";
                    } 
                else 
                    {
                        $repeat_password = $_POST['repeat_password'];
                        if ($password !== $repeat_password) 
                            {
                                $message = "<div class='error'>Las contraseñas no coinciden.</div>";
                            } 
                        else 
                            {
                                $hash = password_hash($password, PASSWORD_DEFAULT);
                                
                                try 
                                    {
                                        $stmt = $pdo->prepare("INSERT INTO usuarios (username, password_hash) VALUES (?, ?)");
                                        $stmt->execute([$username, $hash]);
                                        $message = "<div class='success'>¡Registro completado! Ahora puedes logearte.</div>";
                                    } 
                                catch (PDOException $e) 
                                    {
                                        $message = "<div class='error'>Nombre de usuario no utilizable.</div>";
                                    }
                            }
                    }
            } 
        elseif ($action === 'login') 
            {
                $stmt = $pdo->prepare("SELECT id, password_hash FROM usuarios WHERE username = ?");
                $stmt->execute([$username]);
                $user_data = $stmt->fetch();

                if ($user_data && password_verify($password, $user_data['password_hash'])) 
                    {
                        $_SESSION['user_id'] = $user_data['id'];
                        $_SESSION['username'] = $username;
                        $_SESSION['remember_me'] = isset($_POST['remember']) && $_POST['remember'] === '1';

                        if ($_SESSION['remember_me']) {
                            setcookie(session_name(), session_id(), time() + 60 * 60 * 24 * 30, '/', '', false, true);
                        }

                        header("Location: coleccion.php");
                        exit;
                    } 
                else 
                    {
                        $message = "<div class='error'>Usuario o contraseña incorrectos.</div>";
                    }
            }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShinyDex - Login / Registro</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <h1>🎮 ShinyDex</h1>
        <div class="theme-toggle">
            <label for="themeToggle" style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                <span>🌙 Modo Oscuro</span>
                <input type="checkbox" id="themeToggle" class="toggle-checkbox">
            </label>
        </div>
    </div>

    <div style="display: flex; justify-content: center; align-items: center; min-height: 60vh; padding: 20px;">
        <div class="form-container" style="max-width: 500px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="margin-top: 0; font-size: 2em; background: linear-gradient(135deg, #e3350d 0%, #3b4cca 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                    🌟 ShinyDex
                </h2>
                <p style="color: var(--text-secondary); margin-top: 10px;">Tu colección de Pokémon Shiny</p>
            </div>

            <?= $message ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">👤 Nombre de usuario:</label>
                    <input type="text" id="username" name="username" placeholder="Introduce tu usuario" required>
                </div>

                <div class="form-group">
                    <label for="password">🔒 Contraseña:</label>
                    <input type="password" id="password" name="password" placeholder="Introduce tu contraseña" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        Recordarme en este equipo
                    </label>
                </div>

                <div class="form-group" id="repeat-password-group" style="display: none;">
                    <label for="repeat_password">🔒 Repite la contraseña:</label>
                    <input type="password" id="repeat_password" name="repeat_password" placeholder="Repite tu contraseña">
                </div>

                <button type="submit" name="action" value="login" class="btn btn-secondary" style="width: 100%; margin-bottom: 10px;">
                    ➡️ Iniciar Sesión
                </button>
                <button type="submit" name="action" value="registro" class="btn btn-tertiary" style="width: 100%;" onclick="showRepeatPassword()">
                    ➕ Registrarse
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                <p style="color: var(--text-secondary); font-size: 0.9em; margin: 0;">¿Eres nuevo? Haz clic en "Registrarse" para crear una cuenta</p>
            </div>
        </div>
    </div>

    <script src="theme.js"></script>
    <script>
        function showRepeatPassword() {
            var field = document.getElementById('repeat_password');
            var group = document.getElementById('repeat-password-group');
            group.style.display = 'block';
            field.required = true;
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