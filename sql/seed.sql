INSERT INTO clientes (nome, cpf, telefone, email) VALUES
('Ana Souza', '12345678901', '(11) 99999-1111', 'ana.exemplo@email.test'),
('Bruno Lima', '98765432100', '(11) 98888-2222', NULL);
INSERT INTO equipamentos (id_cliente, tipo, marca, modelo, numero_serie) VALUES
(1, 'Notebook', 'Dell', 'Inspiron 15', 'SERIE-DELL-001'),
(2, 'Computador', 'Lenovo', 'ThinkCentre M70', NULL);
INSERT INTO tecnicos (nome, especialidade) VALUES
('Carla Mendes', 'Hardware'),
('Diego Alves', 'Sistemas operacionais');
INSERT INTO ordens_servico (id_equipamento, id_tecnico, defeito_relatado, diagnostico, status, data_conclusao) VALUES
(1, 1, 'Notebook não liga.', 'Fonte de alimentação com defeito.', 'Concluída', NOW()),
(1, 2, 'Sistema apresenta lentidão.', NULL, 'Aberta', NULL);
