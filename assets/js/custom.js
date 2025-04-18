// assets/js/custom.js
// Scripts personalizados para o sistema de gerenciamento de acessos remotos

$(document).ready(function() {
    // Inicialização dos componentes do Semantic UI
    $('.ui.dropdown').dropdown();
    $('.ui.modal').modal();
    $('.ui.accordion').accordion();
    $('.ui.checkbox').checkbox();
    $('.ui.popup').popup();
    $('.ui.rating').rating();
    
    // Fechar mensagens de alerta
    $('.message .close').on('click', function() {
        $(this).closest('.message').transition('fade');
    });
    
    // Temporizador para mensagens de alerta
    setTimeout(function() {
        $('.ui.message:not(.persistent)').transition('fade');
    }, 5000);
    
    // Destacar a linha da tabela ao passar o mouse
    $('.ui.table tr').hover(
        function() {
            $(this).addClass('active');
        },
        function() {
            $(this).removeClass('active');
        }
    );
    
    // Confirmação para ações de exclusão
    $('.delete-confirm').on('click', function(e) {
        e.preventDefault();
        
        const targetUrl = $(this).attr('href');
        
        $('.ui.basic.modal')
            .modal({
                closable: false,
                onDeny: function() {
                    return true;
                },
                onApprove: function() {
                    window.location.href = targetUrl;
                }
            })
            .modal('show');
    });
    
    // Toggle de visibilidade para campos de senha
    $('.toggle-password').on('click', function() {
        const passwordField = $(this).prev('input');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            $(this).find('i').removeClass('eye').addClass('eye slash');
        } else {
            passwordField.attr('type', 'password');
            $(this).find('i').removeClass('eye slash').addClass('eye');
        }
    });
    
    // Copiar texto para a área de transferência
    $('.copy-to-clipboard').on('click', function() {
        const textToCopy = $(this).data('content');
        const tempInput = $('<input>');
        
        $('body').append(tempInput);
        tempInput.val(textToCopy).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Feedback visual
        $(this).popup({
            content: 'Copiado!',
            position: 'top center',
            on: 'manual'
        }).popup('show');
        
        setTimeout(() => {
            $(this).popup('hide');
        }, 1500);
    });
    
    // Simulação de carregamento para melhorar UX
    $('.simulate-loading').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.addClass('loading disabled');
        
        setTimeout(function() {
            button.removeClass('loading disabled');
            button.html(originalText);
        }, 1000);
    });
    
    // Atualizar dashboard em tempo real (simulação)
    if ($('#dashboard-page').length) {
        setInterval(function() {
            // Aqui você poderia fazer uma chamada AJAX para atualizar as estatísticas
            // Por enquanto, apenas simulamos uma atualização visual
            $('.statistic .value').each(function() {
                $(this).transition('pulse');
            });
        }, 30000); // A cada 30 segundos
    }
    
    // Relatórios - Atualizar gráficos quando mudar os filtros
    $('#filtro-relatorio').on('change', function() {
        $(this).closest('form').submit();
    });
    
    // Responsividade para sidebar em telas pequenas
    const checkScreenSize = function() {
        if ($(window).width() < 768) {
            $('.sidebar').addClass('hidden');
            $('.main-content').css('margin-left', '0');
            
            // Adicionar botão de toggle para o menu
            if ($('#mobile-menu-toggle').length === 0) {
                const toggleButton = $('<button id="mobile-menu-toggle" class="ui icon button"><i class="bars icon"></i></button>');
                $('.top.menu .right.menu').prepend(toggleButton);
                
                toggleButton.on('click', function() {
                    $('.sidebar').toggleClass('hidden visible');
                });
            }
        } else {
            $('.sidebar').removeClass('hidden visible');
            $('.main-content').css('margin-left', '250px');
            $('#mobile-menu-toggle').remove();
        }
    };
    
    // Verificar tamanho da tela no carregamento e quando redimensionar
    checkScreenSize();
    $(window).resize(checkScreenSize);
});