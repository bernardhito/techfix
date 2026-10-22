CREATE TABLE clientes (
  id_cliente INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  cpf CHAR(11) NOT NULL UNIQUE,
  telefone VARCHAR(20) NOT NULL,
  email VARCHAR(150) NULL
);

CREATE TABLE equipamentos (
  id_equipamento INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  id_cliente INTEGER NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  marca VARCHAR(50) NOT NULL,
  modelo VARCHAR(100) NOT NULL,
  numero_serie VARCHAR(100) NULL,
  CONSTRAINT fk_equipamento_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente)
    ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE TABLE tecnicos (
  id_tecnico INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  especialidade VARCHAR(100) NULL
);

CREATE TABLE ordens_servico (
  id_ordem INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  id_equipamento INTEGER NOT NULL,
  id_tecnico INTEGER NOT NULL,
  data_entrada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_conclusao TIMESTAMP NULL,
  defeito_relatado TEXT NOT NULL,
  diagnostico TEXT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'Aberta',
  CONSTRAINT fk_ordem_equipamento FOREIGN KEY (id_equipamento) REFERENCES equipamentos(id_equipamento)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_ordem_tecnico FOREIGN KEY (id_tecnico) REFERENCES tecnicos(id_tecnico)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_ordem_status CHECK (status IN ('Aberta', 'Em manutenção', 'Concluída'))
);
