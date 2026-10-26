SELECT e.*
FROM equipamentos e
WHERE e.id_cliente = 1;

SELECT o.id_ordem, c.nome AS cliente, e.tipo, e.marca, e.modelo,
       t.nome AS tecnico, o.data_entrada, o.status
FROM ordens_servico o
JOIN equipamentos e ON e.id_equipamento = o.id_equipamento
JOIN clientes c ON c.id_cliente = e.id_cliente
JOIN tecnicos t ON t.id_tecnico = o.id_tecnico;

SELECT o.id_ordem, o.data_entrada, o.data_conclusao, o.status,
       o.defeito_relatado, o.diagnostico
FROM ordens_servico o
WHERE o.id_equipamento = 1
ORDER BY o.data_entrada DESC;

SELECT status, COUNT(*) AS quantidade
FROM ordens_servico
GROUP BY status;

SELECT t.id_tecnico, t.nome, COUNT(o.id_ordem) AS quantidade
FROM tecnicos t
LEFT JOIN ordens_servico o ON o.id_tecnico = t.id_tecnico
GROUP BY t.id_tecnico, t.nome
ORDER BY t.nome;
