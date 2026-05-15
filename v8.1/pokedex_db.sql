-- Creo la base de datos principal
CREATE DATABASE IF NOT EXISTS shinydex;
USE shinydex;

-- Creo la tabla de los usuarios
CREATE TABLE IF NOT EXISTS usuarios (id INT AUTO_INCREMENT PRIMARY KEY,
                                        username VARCHAR(50) UNIQUE NOT NULL,
                                        password_hash VARCHAR(255) NOT NULL);

-- Creo una tabla donde estarán los campos de la información que queremos guardar de los Pokémon
CREATE TABLE IF NOT EXISTS pokemon_atrapados (id INT AUTO_INCREMENT PRIMARY KEY,
                                                pokedex_numero INT NOT NULL,
                                                nombre VARCHAR(50) NOT NULL,
                                                nivel INT NOT NULL CHECK (nivel >= 1 AND nivel <= 100),
                                                naturaleza VARCHAR(20),
                                                ball VARCHAR(30),
                                                ruta VARCHAR(100),
                                                encuentros INT NOT NULL,
                                                consola VARCHAR(30),
                                                juego VARCHAR(50),
                                                fecha DATE NOT NULL);

-- Añado una constraint para que cada tabla *pokemon_atrapados* pertenezca a un solo usuario
ALTER TABLE pokemon_atrapados 
ADD COLUMN usuario_id INT NOT NULL;

ALTER TABLE pokemon_atrapados 
ADD CONSTRAINT fk_usuario 
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) 
ON DELETE CASCADE;