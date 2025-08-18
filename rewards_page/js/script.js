document.addEventListener('DOMContentLoaded', function() {
    // Elementos da página
    const codigoInput = document.getElementById('codigo-input');
    const colarBtn = document.getElementById('colar-btn');
    const resgatarBtn = document.getElementById('resgatar-btn');
    const modalConfirmacao = document.getElementById('modal-confirmacao');
    const modalContinuar = document.getElementById('modal-continuar');

    // Código de resgate predefinido
    const codigoResgate = "FF12-AB34-CDE5-DIM4";

    // Função para "colar" o código
    colarBtn.addEventListener('click', function() {
        // Simula colar o código no input
        codigoInput.value = codigoResgate;

        // Oculta o botão de colar
        colarBtn.style.display = 'none';

        // Mostra o botão de resgatar
        resgatarBtn.style.display = 'block';
    });

    // Função para processar o resgate
    resgatarBtn.addEventListener('click', function() {
        // Mostra o modal de confirmação
        modalConfirmacao.style.display = 'flex';
    });

    // Botão para continuar após o resgate
    modalContinuar.addEventListener('click', function() {
        // Fecha o modal
        modalConfirmacao.style.display = 'none';

        // Resetar o formulário
        codigoInput.value = '';
        resgatarBtn.style.display = 'none';
        colarBtn.style.display = 'block'; // Opcional: mostrar de novo
    });
});