# Sistema de assistência técnica

Projeto acadêmico simples para demonstrar cadastros, relacionamentos e consultas usando PHP, PDO e MySQL. Todos os dados do exemplo são fictícios.

## Requisitos

- XAMPP (Apache, PHP 8.1+ e MySQL/MariaDB).
- Extensão `pdo_mysql` habilitada no PHP.
- Navegador moderno.

## Configuração e execução

1. Copie a pasta do projeto para `C:\xampp\htdocs\assistencia-tecnica`.
2. Inicie Apache e MySQL no XAMPP.
3. No phpMyAdmin ou no cliente MySQL, execute `sql/schema.sql`.
4. Opcionalmente execute `sql/seed.sql` para inserir dados fictícios.
5. Se necessário, ajuste usuário, senha e host em `config/database.php` (o padrão é usuário `root` sem senha).
6. Acesse `http://localhost/assistencia-tecnica/public/`.

As operações de alteração usam POST e token CSRF. A aplicação usa prepared statements, validação no servidor, escape HTML e chaves estrangeiras InnoDB com `RESTRICT`, preservando históricos.

## Fluxo para apresentação

1. Cadastre um cliente, informando CPF com 11 dígitos.
2. Cadastre um equipamento e selecione o cliente.
3. Cadastre um técnico.
4. Abra uma ordem selecionando equipamento e técnico.
5. Abra os detalhes da ordem, preencha diagnóstico e altere para **Em manutenção**.
6. Altere para **Concluída** e observe a data de conclusão automática.
7. Reabra a ordem e observe a limpeza da data de conclusão.
8. Consulte o histórico exibido na página da ordem.

## Consultas e diagrama

- Consultas comentadas: `sql/consultas.sql`.
- Dados fictícios: `sql/seed.sql`.
- Diagrama Mermaid consistente com o esquema: `docs/diagrama.md`.

## Validação

O fluxo depende de um servidor PHP e de um MySQL em execução. Neste ambiente de geração não há Apache/MySQL disponível para executar o fluxo com persistência; a validação possível é a inspeção do código e a conferência de consistência entre os nomes usados no PHP, SQL e diagrama. Em um ambiente local, teste também CPF duplicado, exclusões com dependências e reabertura de ordens.
