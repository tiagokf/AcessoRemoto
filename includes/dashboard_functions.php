<?php
// includes/dashboard_functions.php

if (file_exists(dirname(__FILE__) . '/../config/database.php')) {
    require_once dirname(__FILE__) . '/../config/database.php';
} elseif (file_exists(dirname(__FILE__) . '/config/database.php')) { // Caso seja chamado de dentro de includes
    require_once dirname(__FILE__) . '/config/database.php';
}


/**
 * Obtém as estatísticas principais para o dashboard.
 * @return array Contendo total_conexoes, total_acessos, acessos_hoje, total_usuarios.
 */
function getDashboardPrincipalStats() {
    $stats = [
        'total_conexoes' => 0,
        'total_acessos' => 0,
        'acessos_hoje' => 0,
        'total_usuarios' => 0,
    ];

    // Total de conexões
    $result = dbQuery("SELECT COUNT(*) as total FROM conexoes");
    if ($result) $stats['total_conexoes'] = dbFetchAssoc($result)['total'] ?? 0;

    // Total de acessos
    $result = dbQuery("SELECT COUNT(*) as total FROM acessos");
    if ($result) $stats['total_acessos'] = dbFetchAssoc($result)['total'] ?? 0;

    // Acessos hoje
    $result = dbQuery("SELECT COUNT(*) as total FROM acessos WHERE DATE(data_acesso) = CURDATE()");
    if ($result) $stats['acessos_hoje'] = dbFetchAssoc($result)['total'] ?? 0;

    // Total de usuários
    $result = dbQuery("SELECT COUNT(*) as total FROM usuarios");
    if ($result) $stats['total_usuarios'] = dbFetchAssoc($result)['total'] ?? 0;

    return $stats;
}

/**
 * Obtém dados de acessos recentes para o gráfico de linha.
 * @return array Contendo 'labels' (datas) e 'data' (totais).
 */
function getRecentAccessDataForChart() {
    $sql = "SELECT DATE(data_acesso) as data, COUNT(*) as total
            FROM acessos
            WHERE data_acesso >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(data_acesso)
            ORDER BY data ASC"; // Ordenar por data ASC para facilitar o preenchimento
    $result = dbQuery($sql);
    $acessos_por_dia_db = dbFetchAll($result);

    $labels = [];
    $data_points = [];
    $access_map = [];

    if ($acessos_por_dia_db) { // Verificar se $acessos_por_dia_db não é null
        foreach ($acessos_por_dia_db as $acesso) {
            $access_map[$acesso['data']] = (int)$acesso['total'];
        }
    }

    for ($i = 6; $i >= 0; $i--) {
        $current_date_str = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d/m', strtotime($current_date_str));
        $data_points[] = $access_map[$current_date_str] ?? 0;
    }

    return ['labels' => $labels, 'data' => $data_points];
}

/**
 * Obtém as últimas conexões acessadas para a tabela do dashboard.
 * @param int $limit Número de conexões a serem retornadas.
 * @return array Lista das últimas conexões acessadas.
 */
function getLastAccessedConnections($limit = 5) {
    $limit = intval($limit);
    $sql = "SELECT c.id, c.cliente, c.tipo_acesso_remoto, c.id_acesso_remoto, c.senha_acesso_remoto, c.observacoes, MAX(a.data_acesso) as ultimo_acesso
            FROM conexoes c
            LEFT JOIN acessos a ON c.id = a.id_conexao
            GROUP BY c.id
            ORDER BY ultimo_acesso DESC, c.id DESC
            LIMIT {$limit}";
    $result = dbQuery($sql);
    return $result ? dbFetchAll($result) : []; // Retornar array vazio em caso de falha
}

/**
 * Obtém dados de tipos de conexão para o gráfico de barras/pizza.
 * @return array Contendo 'labels' (tipos) e 'data' (quantidades).
 */
function getConnectionTypesDataForChart() {
    $sql = "SELECT tipo_acesso_remoto, COUNT(*) as total FROM conexoes GROUP BY tipo_acesso_remoto ORDER BY total DESC";
    $result = dbQuery($sql);
    $tipos_acesso_db = $result ? dbFetchAll($result) : []; // Retornar array vazio em caso de falha

    $labels = [];
    $data_points = [];

    if (empty($tipos_acesso_db)) {
        $labels[] = 'Sem dados';
        $data_points[] = 1; // Para evitar erro no chartjs com dados vazios
    } else {
        foreach ($tipos_acesso_db as $tipo) {
            $labels[] = $tipo['tipo_acesso_remoto'];
            $data_points[] = (int)$tipo['total'];
        }
    }
    return ['labels' => $labels, 'data' => $data_points];
}

?>
