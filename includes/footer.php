<?php
// includes/footer.php
?>
    <script>
        $(document).ready(function() {
            // Inicializar componentes do Semantic UI
            $('.ui.dropdown').dropdown();
            $('.ui.modal').modal();
            
            // Fechar mensagens de alerta
            $('.message .close').on('click', function() {
                $(this).closest('.message').transition('fade');
            });
        });
    </script>
</body>
</html>