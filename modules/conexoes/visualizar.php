<?php
// modules/conexoes/ver.php
// Página para visualizar detalhes de uma conexão

// Incluir arquivos de configuração
require_once '../../config/config.php';
require_once '../../config/database.php';

// Verificar se o usuário está logado
exigirLogin();

// Verificar se o ID foi informado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    showAlert('ID inválido', 'negative');
    header('Location: listar.php');
    exit;
}

$id = intval($_GET['id']);

// Obter dados da conexão
$result = dbQuery("SELECT c.*, u.nome as usuario_nome 
                  FROM conexoes c 
                  LEFT JOIN usuarios u ON c.id_usuario = u.id 
                  WHERE c.id = $id");

if ($result->num_rows == 0) {
    showAlert('Conexão não encontrada', 'negative');
    header('Location: listar.php');
    exit;
}

$conexao = dbFetchAssoc($result);

// Registrar este acesso
registrarAcesso($id, "Visualização dos detalhes da conexão");

// Obter histórico de acessos
$sql = "SELECT a.*, u.nome as usuario_nome 
        FROM acessos a 
        JOIN usuarios u ON a.id_usuario = u.id 
        WHERE a.id_conexao = $id 
        ORDER BY a.data_acesso DESC 
        LIMIT 10";
$result = dbQuery($sql);
$acessos = dbFetchAll($result);

// Incluir cabeçalho
include '../../includes/header.php';

// Incluir sidebar
include '../../includes/sidebar.php';
?>

<!-- Conteúdo principal -->
<div class="main-content">
    <h1 class="ui header">
        <i class="server icon"></i>
        <div class="content">
            Detalhes da Conexão
            <div class="sub header">Informações sobre a conexão de acesso remoto</div>
        </div>
    </h1>
    
    <div class="ui divider"></div>
    
    <!-- Ações -->
    <div class="ui basic buttons">
        <a href="listar.php" class="ui button">
            <i class="arrow left icon"></i> Voltar
        </a>
        <a href="editar.php?id=<?php echo $id; ?>" class="ui green button">
            <i class="edit icon"></i> Editar
        </a>
    </div>
    
    <div class="ui hidden divider"></div>
    
    <!-- Detalhes da Conexão -->
    <div class="ui segments">
        <div class="ui blue segment">
            <h3 class="ui header">Informações da Conexão</h3>
        </div>
        <div class="ui segment">
            <div class="ui list">
                <div class="item">
                    <div class="header">Cliente</div>
                    <?php echo htmlspecialchars($conexao['cliente']); ?>
                </div>
                <div class="item">
                    <div class="header">Tipo de Acesso</div>
                    <?php echo htmlspecialchars($conexao['tipo_acesso_remoto']); ?>
                </div>
                <div class="item">
                    <div class="header">ID de Acesso</div>
                    <?php echo htmlspecialchars($conexao['id_acesso_remoto']); ?>
                </div>
                <div class="item">
                    <div class="header">Senha de Acesso</div>
                    <div class="ui action input">
                        <input type="password" id="senha" value="<?php echo htmlspecialchars($conexao['senha_acesso_remoto']); ?>" readonly>
                        <button class="ui icon button" onclick="mostrarSenha()">
                            <i class="eye icon"></i>
                        </button>
                        <button class="ui icon button" onclick="copiarSenha()">
                            <i class="copy icon"></i>
                        </button>
                    </div>
                </div>
                <div class="item">
                    <div class="header">Observações</div>
                    <div style="white-space: pre-wrap;"><?php echo nl2br(htmlspecialchars($conexao['observacoes'])); ?></div>
                </div>
                <div class="item">
                    <div class="header">Cadastrado por</div>
                    <?php echo htmlspecialchars($conexao['usuario_nome'] ?? 'Não informado'); ?>
                </div>
                <div class="item">
                    <div class="header">Data de Cadastro</div>
                    <?php echo date('d/m/Y H:i', strtotime($conexao['data_criacao'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Histórico de Acessos -->
    <div class="ui segments">
        <div class="ui blue segment">
            <h3 class="ui header">Histórico de Acessos</h3>
        </div>
        <div class="ui segment">
            <?php if (count($acessos) > 0): ?>
                <table class="ui celled table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Usuário</th>
                            <th>IP</th>
                            <th>Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($acessos as $acesso): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($acesso['data_acesso'])); ?></td>
                                <td><?php echo htmlspecialchars($acesso['usuario_nome']); ?></td>
                                <td><?php echo htmlspecialchars($acesso['ip_acesso']); ?></td>
                                <td><?php echo htmlspecialchars($acesso['detalhes']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="ui message">
                    <p>Nenhum acesso registrado para esta conexão</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    let senhaVisivel = false;
    
    function mostrarSenha() {
        const senhaInput = document.getElementById('senha');
        if (senhaVisivel) {
            senhaInput.type = 'password';
            senhaVisivel = false;
        } else {
            senhaInput.type = 'text';
            senhaVisivel = true;
        }
    }
    
    function copiarSenha() {
        const senhaInput = document.getElementById('senha');
        senhaInput.type = 'text';
        senhaInput.select();
        document.execCommand('copy');
        senhaInput.type = 'password';
        senhaVisivel = false;
        
        // Feedback visual
        $('body')
            .toast({
                class: 'success',
                message: 'Senha copiada para a área de transferência',
                showProgress: 'bottom',
                displayTime: 2000
            });
    }
</script>

<?php
// Incluir rodapé
include '../../includes/footer.php';
?>