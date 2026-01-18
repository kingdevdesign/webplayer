function showModal(html) {
    $('#modal-body').html(html);
    $('#custom-modal').css('display', 'flex').fadeIn();
}

function autoLogin() {
    $('#form-auto').submit();
}

$(document).ready(function() {
    $('#login-form, #form-auto').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        const originalText = btn.text();
        
        btn.prop('disabled', true).html('<div class="spinner"></div>');

        $.ajax({
            url: 'auth.php',
            type: 'POST',
            data: $(this).serialize() + '&ajax_login=1',
            success: function(res) {
                if(res.status === 'success') {
                    showModal('<i class="fas fa-check-circle icon-alert-success"></i><h3>Sucesso!</h3><p>'+res.message+'</p>');
                    setTimeout(() => location.href = 'dashboard.php', 2000);
                } else {
                    showModal('<i class="fas fa-times-circle icon-alert-error"></i><h3>Erro</h3><p>'+res.message+'</p><button class="btn-main" onclick="$(\'#custom-modal\').fadeOut()">VOLTAR</button>');
                    btn.prop('disabled', false).text(originalText);
                }
            }
        });
    });
});
