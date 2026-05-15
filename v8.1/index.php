<?php
session_start();

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
    <title>Login / Registro</title>
    <style>
        body { font-family: Arial; background: #f4f4f9; display: flex; justify-content: center; padding-top: 50px; }
        .box { background: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px;}
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; margin-top: 10px; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .btn-login { background: #e3350d; } 
        .btn-registro { background: #3b4cca; }
        .error { color: red; font-size: 0.9em; } .success { color: green; font-size: 0.9em; }
    </style>
    <script>
        function showRepeatPassword() {
            var field = document.getElementById('repeat_password');
            field.style.display = 'block';
            field.required = true;
        }
    </script>
</head>
<body>
    <div class="box">
        <h2>Login de Usuario</h2>
        <?= $message ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Nombre de usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="password" name="repeat_password" id="repeat_password" placeholder="Repite la contraseña" style="display:none;">
            <button type="submit" name="action" value="login" class="btn-login">Login</button>
            <button type="submit" name="action" value="registro" class="btn-registro" onclick="showRepeatPassword()">Registrar un nuevo usuario</button>
        </form>
    </div>
</body>
</html>