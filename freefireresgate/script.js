document.addEventListener('DOMContentLoaded', function() {
    // Elementos da página
    const codigoExibido = document.getElementById('codigo-exibido');
    const resgatarBtn = document.getElementById('resgatar-btn');
    const modalConfirmacao = document.getElementById('modal-confirmacao');
    const modalContinuar = document.getElementById('modal-continuar');
    const fecharBtn = document.querySelector('.fechar-btn');

    // Função para copiar o código
    resgatarBtn.addEventListener('click', function() {
        // Criar elemento temporário para cópia
        const tempElement = document.createElement('textarea');
        tempElement.value = codigoExibido.textContent;
        document.body.appendChild(tempElement);
        tempElement.select();
        document.execCommand('copy');
        document.body.removeChild(tempElement);

        // Mudar o texto do botão temporariamente
        const textoOriginal = resgatarBtn.textContent;
        resgatarBtn.textContent = 'Código Copiado!';
        setTimeout(function() {
            resgatarBtn.textContent = textoOriginal;
        }, 2000);

        // Opcional: mostrar o modal após um pequeno delay
        setTimeout(function() {
            modalConfirmacao.style.display = 'flex';
        }, 0000);
    });

    // Botão para continuar após o resgate
    modalContinuar.addEventListener('click', function() {
        // Fecha o modal
        modalConfirmacao.style.display = 'none';
    });

    // Botão para fechar a página
    fecharBtn.addEventListener('click', function() {
        window.close(); // Tenta fechar a janela
        // Como fallback, redireciona para uma página em branco
        window.location.href = 'about:blank';
    });
});