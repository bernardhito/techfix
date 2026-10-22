# Sistema de assistência técnica

Projeto acadêmico simples para demonstrar cadastros, relacionamentos e consultas usando PHP, PDO e PostgreSQL. Todos os dados do exemplo são fictícios.

## Requisitos

- Apache, PHP 8.1+ e PostgreSQL 14+.
- Extensão `pdo_pgsql` habilitada no PHP.
- Navegador moderno.

## Configuração e execução

1. Copie a pasta do projeto para `C:\xampp\htdocs\assistencia-tecnica` (o XAMPP será usado apenas para o Apache).
2. Instale e inicie o serviço do PostgreSQL.
3. Crie um banco chamado `assistencia_tecnica` no pgAdmin ou com `createdb -U postgres assistencia_tecnica`.
4. Conectado ao banco, execute `sql/schema.sql` com o pgAdmin ou `psql -U postgres -d assistencia_tecnica -f sql/schema.sql`.
5. Opcionalmente execute `sql/seed.sql` para inserir dados fictícios.
6. Ajuste host, porta, usuário e senha em `config/database.php` (o padrão usa usuário `postgres`, porta `5432` e senha vazia).
7. Se o PHP do XAMPP não tiver o driver, habilite `extension=pdo_pgsql` no `php.ini` e reinicie o Apache.
8. Acesse `http://localhost/assistencia-tecnica/public/`.

As operações de alteração usam POST e token CSRF. A aplicação usa prepared statements, validação no servidor, escape HTML e chaves estrangeiras PostgreSQL com `RESTRICT`, preservando históricos.

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

O fluxo depende de um servidor PHP e de um PostgreSQL em execução. Em um ambiente local, teste também CPF duplicado, exclusões com dependências e reabertura de ordens.
