CREATE DATABASE IF NOT EXISTS assistencia_tecnica
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE assistencia_tecnica;

CREATE TABLE clientes (
  id_cliente INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  email VARCHAR(150) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE equipamentos (
  id_equipamento INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente INT UNSIGNED NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  numero_serie VARCHAR(100) NULL,
  CONSTRAINT fk_equipamento_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tecnicos (
  id_tecnico INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  especialidade VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ordens_servico (
  id_ordem INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_equipamento INT UNSIGNED NOT NULL,
  id_tecnico INT UNSIGNED NOT NULL,
  data_entrada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_conclusao DATETIME NULL,
  defeito_relatado TEXT NOT NULL,
  diagnostico TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Aberta',
  CONSTRAINT fk_ordem_equipamento FOREIGN KEY (id_equipamento) REFERENCES equipamentos(id_equipamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ordem_tecnico FOREIGN KEY (id_tecnico) REFERENCES tecnicos(id_tecnico)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_ordem_status CHECK (status IN ('Aberta', 'Em manutenção', 'Concluída'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
