<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';

const STATUS_OPTIONS = ['Aberta', 'Em manutenção', 'Concluída'];
const EQUIPMENT_TYPES = ['Computador', 'Notebook'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        throw new RuntimeException('Token de segurança inválido. Recarregue a página e tente novamente.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $message;
}

function normalize_cpf(string $cpf): string
{
    return preg_replace('/\D+/', '', $cpf) ?? '';
}

function validate_required(string $value, string $field): string
{
    $value = trim($value);
    if ($value === '') {
        throw new InvalidArgumentException("Informe o campo {$field}.");
    }
    return $value;
}

function existing_id(PDO $pdo, string $table, string $column, int $id, string $label): void
{
    $allowed = ['clientes' => 'id_cliente', 'equipamentos' => 'id_equipamento', 'tecnicos' => 'id_tecnico'];
    if (!isset($allowed[$table]) || $allowed[$table] !== $column || $id <= 0) {
        throw new InvalidArgumentException("{$label} inválido.");
    }
    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$column} = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) {
        throw new InvalidArgumentException("O {$label} selecionado não existe.");
    }
}

function format_date_br(?string $date): string
{
    return $date ? date('d/m/Y H:i', strtotime($date)) : '-';
}

function page_url(string $page, array $params = []): string
{
    return 'index.php?' . http_build_query(array_merge(['pagina' => $page], $params));
}
