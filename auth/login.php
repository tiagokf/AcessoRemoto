<?php
// auth/login.php
// Página de login com design melhorado

// Incluir arquivos de configuração
require_once '../config/config.php';
require_once '../config/database.php';
require_once 'auth.php';

// Verificar se o usuário já está logado
if (estaLogado()) {
    header('Location: ../index.php');
    exit;
}

// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        showAlert('Falha na validação de segurança. Por favor, tente novamente.', 'negative');
        // Não prosseguir com o login
    } else {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';

        // Validar entrada
        if (empty($email) || empty($senha)) {
            showAlert('Por favor, preencha todos os campos.', 'negative');
        } else {
            // Autenticar usuário
            if (autenticarUsuario($email, $senha)) {
                // Login bem-sucedido, redirecionamento feito dentro da função
                header('Location: ../index.php');
                exit;
            } else {
                showAlert('Email ou senha incorretos.', 'negative');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    
    <!-- Semantic UI CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Semantic UI JS -->
    <script src="https://cdn.jsdelivr.net/npm/semantic-ui@2.4.2/dist/semantic.min.js"></script>
    
    <style>
        :root {
            --primary-color: #2185d0;
            --primary-dark: #1a69a4;
            --light-text: #ffffff;
            --dark-text: #333333;
            --light-bg: #f9fafb;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Lato', 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
            padding: 0;
            animation: fadeInUp 0.8s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1.ui.header {
            color: var(--primary-color);
        }
        
        .logo .icon {
            font-size: 3em;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .ui.segment {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border: none;
            background: white;
        }
        
        .ui.dividing.header {
            text-align: center;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-weight: 500;
            color: var(--dark-text);
            border-bottom-color: rgba(0, 0, 0, 0.1);
        }
        
        .ui.form .field label {
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--dark-text);
        }
        
        .ui.form .field .ui.input {
            width: 100%;
        }
        
        .ui.form .field input {
            padding: 12px 15px 12px 45px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .ui.form .field input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 1px var(--primary-color);
        }
        
        .ui.form .field .ui.left.icon.input i {
            opacity: 0.5;
            transition: all 0.3s ease;
            font-size: 1.2em;
        }
        
        .ui.form .field .ui.left.icon.input input:focus + i {
            opacity: 1;
            color: var(--primary-color);
        }
        
        .ui.fluid.button {
            margin-top: 20px;
            padding: 14px;
            font-weight: 500;
            border-radius: 6px;
            background-color: var(--primary-color);
            color: var(--light-text);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .ui.fluid.button:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .ui.fluid.button:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
        }
        
        .ui.fluid.button:hover:after {
            width: 200%;
            height: 200%;
            opacity: 1;
            transition: all 0.5s ease;
        }
        
        .ui.message {
            border-radius: 6px;
            box-shadow: none;
            background-color: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .ui.negative.message {
            border-left-color: #db2828;
            background-color: #fff6f6;
        }
        
        .ui.positive.message {
            border-left-color: #21ba45;
            background-color: #f6fff6;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            color: rgba(0, 0, 0, 0.5);
            font-size: 0.9em;
        }
        
        /* Animação de carregamento no botão */
        .ui.loading.button:after {
            border-color: white transparent transparent;
        }
        
        /* Responsividade */
        @media only screen and (max-width: 480px) {
            .login-container {
                width: 90%;
            }
            
            .ui.segment {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="server icon"></i>
            <h1 class="ui header">
                <?php echo SITE_NAME; ?>
                <div class="sub header">Gerenciamento de Acessos Remotos</div>
            </h1>
        </div>
        
        <?php displayAlert(); ?>
        
        <div class="ui segment">
            <form class="ui form" method="POST" action="" id="loginForm">
                <h3 class="ui dividing header">Login</h3>
                
                <div class="field">
                    <label>E-mail</label>
                    <div class="ui left icon input">
                        <input type="email" name="email" id="email" placeholder="Digite seu e-mail" required>
                        <i class="envelope icon"></i>
                    </div>
                </div>
                
                <div class="field">
                    <label>Senha</label>
                    <div class="ui left icon input">
                        <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required>
                        <i class="lock icon"></i>
                    </div>
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

                <button class="ui fluid primary button" type="submit" id="loginBtn">
                    Entrar
                </button>
            </form>
        </div>
        
        <div class="footer">
            &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> - Todos os direitos reservados
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Exibir efeito de carregamento ao submeter o form
            $('#loginForm').on('submit', function() {
                $('#loginBtn').addClass('loading disabled');
            });
            
            // Fechar mensagens de alerta
            $('.message .close').on('click', function() {
                $(this).closest('.message').transition('fade');
            });
            
            // Auto-fechar mensagens após 5 segundos
            setTimeout(function() {
                $('.message').transition('fade');
            }, 5000);
            
            // Adicionar evento para campo de email
            $('#email').on('focus', function() {
                $(this).parent().addClass('focus');
            }).on('blur', function() {
                $(this).parent().removeClass('focus');
            });
            
            // Adicionar evento para campo de senha
            $('#senha').on('focus', function() {
                $(this).parent().addClass('focus');
            }).on('blur', function() {
                $(this).parent().removeClass('focus');
            });
            
            // Focar no primeiro campo automaticamente
            $('#email').focus();
        });
    </script>
</body>
</html>