CREATE DATABASE IF NOT EXISTS webgenerator
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE webgenerator;

CREATE TABLE IF NOT EXISTS usuarios (
  idUsuario     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email         VARCHAR(255) NOT NULL,
  password      VARCHAR(255) NOT NULL,          -- hash generado con password_hash()
  fechaRegistro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idUsuario),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS webs (
  idWeb         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  idUsuario     INT UNSIGNED NOT NULL,
  dominio       VARCHAR(100) NOT NULL,
  fechaCreacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (idWeb),
  UNIQUE KEY uq_webs_dominio (dominio),
  CONSTRAINT fk_webs_usuario FOREIGN KEY (idUsuario)
    REFERENCES usuarios (idUsuario) ON DELETE CASCADE
) ENGINE=InnoDB;
