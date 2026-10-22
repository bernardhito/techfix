<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$pdo = null;
$error = null;
$page = $_GET['pagina'] ?? 'inicio';
$action = $_POST['action'] ?? '';

try {
    $pdo = get_pdo();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        handle_post($pdo, $action);
    }
} catch (Throwable $exception) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = $exception instanceof PDOException && $exception->errorInfo[1] === 1062
            ? 'Já existe um cliente cadastrado com este CPF.'
            : ($exception instanceof PDOException && in_array($exception->errorInfo[1] ?? 0, [1451, 1452], true)
                ? 'A operação não pode ser realizada porque há registros relacionados.'
                : $exception->getMessage());
    } else {
        $error = 'Não foi possível conectar ao banco. Confira a configuração e importe sql/schema.sql.';
    }
}

function handle_post(PDO $pdo, string $action): never
{
    switch ($action) {
        case 'cliente_salvar':
            $id = (int) ($_POST['id_cliente'] ?? 0);
            $nome = validate_required($_POST['nome'] ?? '', 'nome');
            $cpf = normalize_cpf($_POST['cpf'] ?? '');
            if (strlen($cpf) !== 11) {
                throw new InvalidArgumentException('O CPF deve conter 11 dígitos.');
            }
            $telefone = validate_required($_POST['telefone'] ?? '', 'telefone');
            $email = trim($_POST['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Informe um e-mail válido.');
            }
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE clientes SET nome=?, cpf=?, telefone=?, email=? WHERE id_cliente=?');
                $stmt->execute([$nome, $cpf, $telefone, $email ?: null, $id]);
                flash('success', 'Cliente atualizado com sucesso.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO clientes (nome, cpf, telefone, email) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nome, $cpf, $telefone, $email ?: null]);
                flash('success', 'Cliente cadastrado com sucesso.');
            }
            redirect(page_url('clientes'));
        case 'cliente_excluir':
            $id = (int) ($_POST['id_cliente'] ?? 0);
            $stmt = $pdo->prepare('DELETE FROM clientes WHERE id_cliente=?');
            $stmt->execute([$id]);
            flash('success', 'Cliente excluído com sucesso.');
            redirect(page_url('clientes'));
        case 'equipamento_salvar':
            $id = (int) ($_POST['id_equipamento'] ?? 0);
            $cliente = (int) ($_POST['id_cliente'] ?? 0);
            existing_id($pdo, 'clientes', 'id_cliente', $cliente, 'cliente');
            $tipo = $_POST['tipo'] ?? '';
            if (!in_array($tipo, EQUIPMENT_TYPES, true)) {
                throw new InvalidArgumentException('Selecione um tipo de equipamento válido.');
            }
            $marca = validate_required($_POST['marca'] ?? '', 'marca');
            $modelo = validate_required($_POST['modelo'] ?? '', 'modelo');
            $serie = trim($_POST['numero_serie'] ?? '');
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE equipamentos SET tipo=?, marca=?, modelo=?, numero_serie=? WHERE id_equipamento=?');
                $stmt->execute([$tipo, $marca, $modelo, $serie ?: null, $id]);
                flash('success', 'Equipamento atualizado. O proprietário foi preservado.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO equipamentos (id_cliente, tipo, marca, modelo, numero_serie) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$cliente, $tipo, $marca, $modelo, $serie ?: null]);
                flash('success', 'Equipamento cadastrado com sucesso.');
            }
            redirect(page_url('equipamentos'));
        case 'equipamento_excluir':
            $stmt = $pdo->prepare('DELETE FROM equipamentos WHERE id_equipamento=?');
            $stmt->execute([(int) $_POST['id_equipamento']]);
            flash('success', 'Equipamento excluído com sucesso.');
            redirect(page_url('equipamentos'));
        case 'tecnico_salvar':
            $id = (int) ($_POST['id_tecnico'] ?? 0);
            $nome = validate_required($_POST['nome'] ?? '', 'nome');
            $especialidade = trim($_POST['especialidade'] ?? '');
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE tecnicos SET nome=?, especialidade=? WHERE id_tecnico=?');
                $stmt->execute([$nome, $especialidade ?: null, $id]);
                flash('success', 'Técnico atualizado com sucesso.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO tecnicos (nome, especialidade) VALUES (?, ?)');
                $stmt->execute([$nome, $especialidade ?: null]);
                flash('success', 'Técnico cadastrado com sucesso.');
            }
            redirect(page_url('tecnicos'));
        case 'tecnico_excluir':
            $stmt = $pdo->prepare('DELETE FROM tecnicos WHERE id_tecnico=?');
            $stmt->execute([(int) $_POST['id_tecnico']]);
            flash('success', 'Técnico excluído com sucesso.');
            redirect(page_url('tecnicos'));
        case 'ordem_salvar':
            $equipment = (int) ($_POST['id_equipamento'] ?? 0);
            $technician = (int) ($_POST['id_tecnico'] ?? 0);
            existing_id($pdo, 'equipamentos', 'id_equipamento', $equipment, 'equipamento');
            existing_id($pdo, 'tecnicos', 'id_tecnico', $technician, 'técnico');
            $defeito = validate_required($_POST['defeito_relatado'] ?? '', 'defeito relatado');
            $stmt = $pdo->prepare('INSERT INTO ordens_servico (id_equipamento, id_tecnico, defeito_relatado) VALUES (?, ?, ?)');
            $stmt->execute([$equipment, $technician, $defeito]);
            flash('success', 'Ordem de serviço aberta com sucesso.');
            redirect(page_url('ordens'));
        case 'ordem_atualizar':
            $id = (int) ($_POST['id_ordem'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!in_array($status, STATUS_OPTIONS, true)) {
                throw new InvalidArgumentException('Status inválido.');
            }
            $diagnostico = trim($_POST['diagnostico'] ?? '');
            $data = $status === 'Concluída' ? date('Y-m-d H:i:s') : null;
            $stmt = $pdo->prepare('UPDATE ordens_servico SET diagnostico=?, status=?, data_conclusao=? WHERE id_ordem=?');
            $stmt->execute([$diagnostico ?: null, $status, $data, $id]);
            flash('success', 'Ordem atualizada com sucesso.');
            redirect(page_url('ordem', ['id' => $id]));
        default:
            throw new InvalidArgumentException('Operação não reconhecida.');
    }
}

function fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

$flash = get_flash();
$edit = null;
$search = trim($_GET['busca'] ?? '');
$status_filter = $_GET['status'] ?? '';
if ($pdo) {
    if ($page === 'inicio') {
        $resumo = [
            'clientes' => (int) (fetch_one($pdo, 'SELECT COUNT(*) AS total FROM clientes')['total'] ?? 0),
            'equipamentos' => (int) (fetch_one($pdo, 'SELECT COUNT(*) AS total FROM equipamentos')['total'] ?? 0),
            'tecnicos' => (int) (fetch_one($pdo, 'SELECT COUNT(*) AS total FROM tecnicos')['total'] ?? 0),
            'ordens' => (int) (fetch_one($pdo, 'SELECT COUNT(*) AS total FROM ordens_servico')['total'] ?? 0),
        ];
    } elseif ($page === 'clientes') {
        $edit = isset($_GET['editar']) ? fetch_one($pdo, 'SELECT * FROM clientes WHERE id_cliente=?', [(int) $_GET['editar']]) : null;
        $clientes = fetch_all($pdo, 'SELECT * FROM clientes WHERE nome LIKE ? OR cpf LIKE ? ORDER BY nome', ["%{$search}%", "%{$search}%"]);
    } elseif ($page === 'equipamentos') {
        $edit = isset($_GET['editar']) ? fetch_one($pdo, 'SELECT * FROM equipamentos WHERE id_equipamento=?', [(int) $_GET['editar']]) : null;
        $equipamentos = fetch_all($pdo, 'SELECT e.*, c.nome AS cliente_nome FROM equipamentos e JOIN clientes c ON c.id_cliente=e.id_cliente ORDER BY e.id_equipamento DESC');
        $clientes = fetch_all($pdo, 'SELECT * FROM clientes ORDER BY nome');
    } elseif ($page === 'tecnicos') {
        $edit = isset($_GET['editar']) ? fetch_one($pdo, 'SELECT * FROM tecnicos WHERE id_tecnico=?', [(int) $_GET['editar']]) : null;
        $tecnicos = fetch_all($pdo, 'SELECT * FROM tecnicos ORDER BY nome');
    } elseif ($page === 'ordens') {
        $ordens = fetch_all($pdo, 'SELECT o.*, e.tipo, e.marca, e.modelo, c.nome AS cliente_nome, t.nome AS tecnico_nome FROM ordens_servico o JOIN equipamentos e ON e.id_equipamento=o.id_equipamento JOIN clientes c ON c.id_cliente=e.id_cliente JOIN tecnicos t ON t.id_tecnico=o.id_tecnico WHERE (? = "" OR o.status = ?) ORDER BY o.id_ordem DESC', [$status_filter, $status_filter]);
        $equipamentos = fetch_all($pdo, 'SELECT e.id_equipamento, e.tipo, e.marca, e.modelo, c.nome AS cliente_nome FROM equipamentos e JOIN clientes c ON c.id_cliente=e.id_cliente ORDER BY c.nome, e.marca');
        $tecnicos = fetch_all($pdo, 'SELECT * FROM tecnicos ORDER BY nome');
    } elseif ($page === 'ordem' && isset($_GET['id'])) {
        $ordem = fetch_one($pdo, 'SELECT o.*, e.tipo, e.marca, e.modelo, e.numero_serie, c.nome AS cliente_nome, t.nome AS tecnico_nome FROM ordens_servico o JOIN equipamentos e ON e.id_equipamento=o.id_equipamento JOIN clientes c ON c.id_cliente=e.id_cliente JOIN tecnicos t ON t.id_tecnico=o.id_tecnico WHERE o.id_ordem=?', [(int) $_GET['id']]);
        $historico = $ordem ? fetch_all($pdo, 'SELECT id_ordem, data_entrada, data_conclusao, status, defeito_relatado FROM ordens_servico WHERE id_equipamento=? ORDER BY data_entrada DESC', [$ordem['id_equipamento']]) : [];
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Assistência Técnica</title><link rel="stylesheet" href="style.css"></head>
<body>
<header><div class="container"><div class="brand"><div class="brand-mark">T</div><div><h1>TechFix</h1><span>Gestão de assistência técnica</span></div></div><nav>
<a class="<?= $page === 'inicio' ? 'active' : '' ?>" href="index.php">Início</a><a class="<?= $page === 'clientes' ? 'active' : '' ?>" href="<?= e(page_url('clientes')) ?>">Clientes</a><a class="<?= $page === 'equipamentos' ? 'active' : '' ?>" href="<?= e(page_url('equipamentos')) ?>">Equipamentos</a><a class="<?= $page === 'tecnicos' ? 'active' : '' ?>" href="<?= e(page_url('tecnicos')) ?>">Técnicos</a><a class="<?= in_array($page, ['ordens', 'ordem'], true) ? 'active' : '' ?>" href="<?= e(page_url('ordens')) ?>">Ordens de serviço</a>
</nav></div></header>
<main class="container">
<?php if ($flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if (!$pdo): ?><section class="card"><h2>Banco indisponível</h2><p>Configure o acesso em <code>config/database.php</code> e importe <code>sql/schema.sql</code>.</p></section>
<?php elseif ($page === 'inicio'): ?>
<section class="hero"><div><p class="eyebrow">PAINEL DE CONTROLE</p><h2>Olá, seja bem-vindo ao TechFix</h2><p>Tenha uma visão clara dos atendimentos e mantenha sua operação organizada em um só lugar.</p><a class="button hero-action" href="<?= e(page_url('ordens')) ?>">Ver ordens de serviço <span aria-hidden="true">→</span></a></div><div class="hero-shape" aria-hidden="true">✦</div></section>
<section class="stats" aria-label="Resumo do sistema">
<a class="stat-card" href="<?= e(page_url('clientes')) ?>"><span class="stat-icon blue">♙</span><span><strong><?= e((string) $resumo['clientes']) ?></strong><small>Clientes cadastrados</small></span><span class="stat-arrow">→</span></a>
<a class="stat-card" href="<?= e(page_url('equipamentos')) ?>"><span class="stat-icon purple">▣</span><span><strong><?= e((string) $resumo['equipamentos']) ?></strong><small>Equipamentos</small></span><span class="stat-arrow">→</span></a>
<a class="stat-card" href="<?= e(page_url('tecnicos')) ?>"><span class="stat-icon orange">⚙</span><span><strong><?= e((string) $resumo['tecnicos']) ?></strong><small>Técnicos ativos</small></span><span class="stat-arrow">→</span></a>
<a class="stat-card" href="<?= e(page_url('ordens')) ?>"><span class="stat-icon green">✓</span><span><strong><?= e((string) $resumo['ordens']) ?></strong><small>Ordens registradas</small></span><span class="stat-arrow">→</span></a>
</section>
<section class="quick-actions"><div><p class="eyebrow">ACESSO RÁPIDO</p><h2>O que você deseja fazer?</h2></div><div class="action-grid"><a class="action-card" href="<?= e(page_url('clientes')) ?>"><span>＋</span><strong>Novo cliente</strong><small>Cadastre um cliente</small></a><a class="action-card" href="<?= e(page_url('equipamentos')) ?>"><span>▣</span><strong>Novo equipamento</strong><small>Registre um aparelho</small></a><a class="action-card" href="<?= e(page_url('ordens')) ?>"><span>↗</span><strong>Abrir ordem</strong><small>Inicie um atendimento</small></a></div></section>
<?php elseif ($page === 'clientes'): ?>
<section class="card"><h2><?= $edit ? 'Editar cliente' : 'Novo cliente' ?></h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cliente_salvar"><input type="hidden" name="id_cliente" value="<?= e($edit['id_cliente'] ?? '') ?>"><div class="grid"><label>Nome *<input name="nome" maxlength="100" required value="<?= e($edit['nome'] ?? '') ?>"></label><label>CPF *<input name="cpf" maxlength="14" required placeholder="Somente números" value="<?= e($edit['cpf'] ?? '') ?>"></label><label>Telefone *<input name="telefone" maxlength="20" required value="<?= e($edit['telefone'] ?? '') ?>"></label><label>E-mail<input type="email" name="email" maxlength="150" value="<?= e($edit['email'] ?? '') ?>"></label></div><button>Salvar</button><?php if ($edit): ?> <a class="button secondary" href="<?= e(page_url('clientes')) ?>">Cancelar</a><?php endif; ?></form></section>
<section class="card"><div class="title-row"><h2>Clientes cadastrados</h2><form class="inline"><input type="hidden" name="pagina" value="clientes"><input name="busca" placeholder="Nome ou CPF" value="<?= e($search) ?>"><button>Pesquisar</button></form></div><?php if (!$clientes): ?><p class="empty">Nenhum cliente encontrado.</p><?php else: ?><table><tr><th>ID</th><th>Nome</th><th>CPF</th><th>Telefone</th><th>Ações</th></tr><?php foreach ($clientes as $item): ?><tr><td><?= e((string) $item['id_cliente']) ?></td><td><?= e($item['nome']) ?></td><td><?= e($item['cpf']) ?></td><td><?= e($item['telefone']) ?></td><td><a href="<?= e(page_url('clientes', ['editar' => $item['id_cliente']])) ?>">Editar</a> <form class="inline" method="post" onsubmit="return confirm('Excluir este cliente?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cliente_excluir"><input type="hidden" name="id_cliente" value="<?= e((string) $item['id_cliente']) ?>"><button class="link danger">Excluir</button></form></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<?php elseif ($page === 'equipamentos'): ?>
<section class="card"><h2><?= $edit ? 'Editar equipamento' : 'Novo equipamento' ?></h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="equipamento_salvar"><input type="hidden" name="id_equipamento" value="<?= e($edit['id_equipamento'] ?? '') ?>"><?php if ($edit): ?><input type="hidden" name="id_cliente" value="<?= e($edit['id_cliente']) ?>"><?php endif; ?><div class="grid"><label>Proprietário *<?php if ($edit): ?><input disabled value="<?= e(fetch_one($pdo, 'SELECT nome FROM clientes WHERE id_cliente=?', [$edit['id_cliente']])['nome'] ?? '') ?>"><?php else: ?><select name="id_cliente" required><option value="">Selecione</option><?php foreach ($clientes as $c): ?><option value="<?= e((string) $c['id_cliente']) ?>"><?= e($c['nome']) ?></option><?php endforeach; ?></select><?php endif; ?></label><label>Tipo *<select name="tipo" required><?php foreach (EQUIPMENT_TYPES as $type): ?><option <?= ($edit['tipo'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label><label>Marca *<input name="marca" maxlength="50" required value="<?= e($edit['marca'] ?? '') ?>"></label><label>Modelo *<input name="modelo" maxlength="100" required value="<?= e($edit['modelo'] ?? '') ?>"></label><label>Número de série<input name="numero_serie" maxlength="100" value="<?= e($edit['numero_serie'] ?? '') ?>"></label></div><button>Salvar</button></form></section>
<section class="card"><h2>Equipamentos cadastrados</h2><?php if (!$equipamentos): ?><p class="empty">Nenhum equipamento cadastrado.</p><?php else: ?><table><tr><th>ID</th><th>Proprietário</th><th>Tipo</th><th>Marca/modelo</th><th>Ações</th></tr><?php foreach ($equipamentos as $item): ?><tr><td><?= e((string) $item['id_equipamento']) ?></td><td><?= e($item['cliente_nome']) ?></td><td><?= e($item['tipo']) ?></td><td><?= e($item['marca'] . ' ' . $item['modelo']) ?></td><td><a href="<?= e(page_url('equipamentos', ['editar' => $item['id_equipamento']])) ?>">Editar</a> <form class="inline" method="post" onsubmit="return confirm('Excluir este equipamento?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="equipamento_excluir"><input type="hidden" name="id_equipamento" value="<?= e((string) $item['id_equipamento']) ?>"><button class="link danger">Excluir</button></form></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<?php elseif ($page === 'tecnicos'): ?>
<section class="card"><h2><?= $edit ? 'Editar técnico' : 'Novo técnico' ?></h2><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="tecnico_salvar"><input type="hidden" name="id_tecnico" value="<?= e($edit['id_tecnico'] ?? '') ?>"><div class="grid"><label>Nome *<input name="nome" maxlength="100" required value="<?= e($edit['nome'] ?? '') ?>"></label><label>Especialidade<input name="especialidade" maxlength="100" value="<?= e($edit['especialidade'] ?? '') ?>"></label></div><button>Salvar</button></form></section>
<section class="card"><h2>Técnicos cadastrados</h2><?php if (!$tecnicos): ?><p class="empty">Nenhum técnico cadastrado.</p><?php else: ?><table><tr><th>ID</th><th>Nome</th><th>Especialidade</th><th>Ações</th></tr><?php foreach ($tecnicos as $item): ?><tr><td><?= e((string) $item['id_tecnico']) ?></td><td><?= e($item['nome']) ?></td><td><?= e($item['especialidade']) ?></td><td><a href="<?= e(page_url('tecnicos', ['editar' => $item['id_tecnico']])) ?>">Editar</a> <form class="inline" method="post" onsubmit="return confirm('Excluir este técnico?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="tecnico_excluir"><input type="hidden" name="id_tecnico" value="<?= e((string) $item['id_tecnico']) ?>"><button class="link danger">Excluir</button></form></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<?php elseif ($page === 'ordens'): ?>
<section class="card"><h2>Abrir ordem de serviço</h2><?php if (!$equipamentos || !$tecnicos): ?><p class="notice">Cadastre pelo menos um equipamento e um técnico antes de abrir uma ordem.</p><?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ordem_salvar"><div class="grid"><label>Equipamento *<select name="id_equipamento" required><option value="">Selecione</option><?php foreach ($equipamentos as $item): ?><option value="<?= e((string) $item['id_equipamento']) ?>"><?= e($item['cliente_nome'] . ' - ' . $item['marca'] . ' ' . $item['modelo'] . ' (código ' . $item['id_equipamento'] . ')') ?></option><?php endforeach; ?></select></label><label>Técnico *<select name="id_tecnico" required><option value="">Selecione</option><?php foreach ($tecnicos as $item): ?><option value="<?= e((string) $item['id_tecnico']) ?>"><?= e($item['nome']) ?></option><?php endforeach; ?></select></label><label class="wide">Defeito relatado *<textarea name="defeito_relatado" required></textarea></label></div><button>Abrir ordem</button></form><?php endif; ?></section>
<section class="card"><div class="title-row"><h2>Ordens de serviço</h2><form class="inline"><input type="hidden" name="pagina" value="ordens"><select name="status"><option value="">Todos os status</option><?php foreach (STATUS_OPTIONS as $status): ?><option <?= $status_filter === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select><button>Filtrar</button></form></div><?php if (!$ordens): ?><p class="empty">Nenhuma ordem encontrada.</p><?php else: ?><table><tr><th>Código</th><th>Cliente</th><th>Equipamento</th><th>Técnico</th><th>Entrada</th><th>Status</th><th></th></tr><?php foreach ($ordens as $item): ?><tr><td>#<?= e((string) $item['id_ordem']) ?></td><td><?= e($item['cliente_nome']) ?></td><td><?= e($item['marca'] . ' ' . $item['modelo']) ?></td><td><?= e($item['tecnico_nome']) ?></td><td><?= e(format_date_br($item['data_entrada'])) ?></td><td><span class="status"><?= e($item['status']) ?></span></td><td><a href="<?= e(page_url('ordem', ['id' => $item['id_ordem']])) ?>">Visualizar</a></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<?php elseif ($page === 'ordem' && !empty($ordem)): ?>
<section class="card"><h2>Ordem #<?= e((string) $ordem['id_ordem']) ?></h2><p><strong>Cliente:</strong> <?= e($ordem['cliente_nome']) ?> · <strong>Equipamento:</strong> <?= e($ordem['tipo'] . ' ' . $ordem['marca'] . ' ' . $ordem['modelo']) ?> · <strong>Técnico:</strong> <?= e($ordem['tecnico_nome']) ?></p><p><strong>Entrada:</strong> <?= e(format_date_br($ordem['data_entrada'])) ?> · <strong>Conclusão:</strong> <?= e(format_date_br($ordem['data_conclusao'])) ?></p><p><strong>Defeito relatado:</strong><br><?= nl2br(e($ordem['defeito_relatado'])) ?></p><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="ordem_atualizar"><input type="hidden" name="id_ordem" value="<?= e((string) $ordem['id_ordem']) ?>"><label>Diagnóstico<textarea name="diagnostico"><?= e($ordem['diagnostico']) ?></textarea></label><label>Status<select name="status"><?php foreach (STATUS_OPTIONS as $status): ?><option <?= $ordem['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label><button>Atualizar ordem</button></form></section>
<section class="card"><h2>Histórico deste equipamento</h2><table><tr><th>Ordem</th><th>Entrada</th><th>Conclusão</th><th>Status</th><th>Defeito</th></tr><?php foreach ($historico as $item): ?><tr><td><a href="<?= e(page_url('ordem', ['id' => $item['id_ordem']])) ?>">#<?= e((string) $item['id_ordem']) ?></a></td><td><?= e(format_date_br($item['data_entrada'])) ?></td><td><?= e(format_date_br($item['data_conclusao'])) ?></td><td><?= e($item['status']) ?></td><td><?= e($item['defeito_relatado']) ?></td></tr><?php endforeach; ?></table></section>
<?php endif; ?>
</main><footer><div class="container">Projeto acadêmico de Banco de Dados · PostgreSQL + PHP PDO</div></footer></body></html>
