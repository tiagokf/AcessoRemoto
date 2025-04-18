<?php
// modules/conexoes/exportar.php
// Script para exportar conexões para CSV

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
requireLogin();

// Configurar cabeçalhos para download de CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="conexoes_' . date('Y-m-d') . '.csv"');

// Criar o manipulador de saída
$output = fopen('php://output', 'w');

// UTF-8 BOM para suporte a caracteres especiais no Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeçalho do CSV
fputcsv($output, ['ID', 'Cliente', 'Tipo de Acesso', 'ID de Acesso', 'Senha', 'Observações', 'Data de Criação']);

// Reutilizar a mesma lógica de filtros do listar.php
$busca = isset($_GET['busca']) ? dbEscape($_GET['busca']) : '';
$filtro_tipo = isset($_GET['filtro_tipo']) ? dbEscape($_GET['filtro_tipo']) : '';
$filtro_cliente = isset($_GET['filtro_cliente']) ? dbEscape($_GET['filtro_cliente']) : '';
$filtro_id_acesso = isset($_GET['filtro_id_acesso']) ? dbEscape($_GET['filtro_id_acesso']) : '';
$filtro_observacoes = isset($_GET['filtro_observacoes']) ? dbEscape($_GET['filtro_observacoes']) : '';
$filtro_data_inicio = isset($_GET['filtro_data_inicio']) ? dbEscape($_GET['filtro_data_inicio']) : '';
$filtro_data_fim = isset($_GET['filtro_data_fim']) ? dbEscape($_GET['filtro_data_fim']) : '';
$filtro_sem_acesso = isset($_GET['filtro_sem_acesso']) ? true : false;
$filtro_ordem = isset($_GET['filtro_ordem']) ? dbEscape($_GET['filtro_ordem']) : 'cliente_asc';

// Construir a consulta SQL
$whereClause = '';
$conditions = array();
if (!empty($busca)) {
    $conditions[] = "(cliente LIKE '%$busca%' OR tipo_acesso_remoto LIKE '%$busca%' OR id_acesso_remoto LIKE '%$busca%')";
}
if (!empty($filtro_tipo)) {
    $conditions[] = "tipo_acesso_remoto = '$filtro_tipo'";
}
if (!empty($filtro_cliente)) {
    $conditions[] = "cliente LIKE '%$filtro_cliente%'";
}
if (!empty($filtro_id_acesso)) {
    $conditions[] = "id_acesso_remoto LIKE '%$filtro_id_acesso%'";
}
if (!empty($filtro_observacoes)) {
    $conditions[] = "observacoes LIKE '%$filtro_observacoes%'";
}

// Adicionar condições de data usando uma subconsulta para o último acesso
if (!empty($filtro_data_inicio) || !empty($filtro_data_fim) || $filtro_sem_acesso) {
    // Subconsulta para acessos
    $acessosSubquery = "SELECT id_conexao, MAX(data_acesso) as ultimo_acesso FROM acessos GROUP BY id_conexao";
    
    if ($filtro_sem_acesso) {
        $conditions[] = "c.id NOT IN (SELECT id_conexao FROM acessos)";
    } else {
        if (!empty($filtro_data_inicio)) {
            $date_inicio = date('Y-m-d', strtotime($filtro_data_inicio));
            $acessosConditions[] = "ultimo_acesso >= '$date_inicio 00:00:00'";
        }
        
        if (!empty($filtro_data_fim)) {
            $date_fim = date('Y-m-d', strtotime($filtro_data_fim));
            $acessosConditions[] = "ultimo_acesso <= '$date_fim 23:59:59'";
        }
        
        if (!empty($acessosConditions)) {
            $acessosWhere = " WHERE " . implode(" AND ", $acessosConditions);
            $acessosSubquery .= $acessosWhere;
            $conditions[] = "c.id IN (SELECT id_conexao FROM ($acessosSubquery) as a)";
        }
    }
}

if (count($conditions) > 0) {
    $whereClause = ' WHERE ' . implode(' AND ', $conditions);
}

// Definir ordem
$orderClause = 'ORDER BY cliente ASC';
switch ($filtro_ordem) {
    case 'cliente_desc':
        $orderClause = 'ORDER BY cliente DESC';
        break;
    case 'tipo_asc':
        $orderClause = 'ORDER BY tipo_acesso_remoto ASC';
        break;
    case 'tipo_desc':
        $orderClause = 'ORDER BY tipo_acesso_remoto DESC';
        break;
    case 'id_asc':
        $orderClause = 'ORDER BY id ASC';
        break;
    case 'id_desc':
        $orderClause = 'ORDER BY id DESC';
        break;
    case 'recente':
        $orderClause = 'ORDER BY id DESC';
        break;
    case 'antigo':
        $orderClause = 'ORDER BY id ASC';
        break;
    default:
        $orderClause = 'ORDER BY cliente ASC';
}

// Para exportação, não há limite de registros
$sql = "SELECT c.* FROM conexoes c $whereClause $orderClause";
$result = dbQuery($sql);

// Exportar dados
while ($row = dbFetchAssoc($result)) {
    // Formatar datas se necessário
    $data_criacao = !empty($row['data_criacao']) ? date('d/m/Y H:i', strtotime($row['data_criacao'])) : '';
    
    // Escrever linha no CSV
    fputcsv($output, [
        $row['id'],
        $row['cliente'],
        $row['tipo_acesso_remoto'],
        $row['id_acesso_remoto'],
        $row['senha_acesso_remoto'],
        $row['observacoes'],
        $data_criacao
    ]);
}

// Fechar o arquivo e encerrar
fclose($output);
exit; 