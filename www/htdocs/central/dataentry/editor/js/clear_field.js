document.addEventListener("DOMContentLoaded", function () {
    var seletores = 'input[type="text"][name^="field"], input[type="text"][name^="tag"], textarea[name^="field"], textarea[name^="tag"]';
    var campos = document.querySelectorAll(seletores);

    campos.forEach(function (campo) {
        if (campo.readOnly || campo.disabled || campo.type === 'hidden' || campo.style.display === 'none') {
            return;
        }

        var btnLimpar = document.createElement('a');
        btnLimpar.href = 'javascript:void(0);';
        btnLimpar.title = 'Limpar campo';
        btnLimpar.className = 'btn-limpar-abcd';
        btnLimpar.innerHTML = '<i class="fas fa-eraser"></i>';
        btnLimpar.style.marginLeft = '4px'; // Um pequeno respiro para não colar na caixa

        campo.parentNode.insertBefore(btnLimpar, campo.nextSibling);
    });
});

// A DELEGAÇÃO DE EVENTOS MÁGICA:
// Colocamos o evento de clique no documento inteiro. Assim, até os botões
// clonados pela função de adicionar linha vão funcionar perfeitamente!
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.btn-limpar-abcd');
    if (btn) {
        e.preventDefault();
        // O campo a limpar é sempre o elemento imediatamente antes do botão
        var campo = btn.previousElementSibling;
        if (campo && (campo.tagName === 'INPUT' || campo.tagName === 'TEXTAREA')) {
            campo.value = '';
            campo.focus();
        }
    }
});