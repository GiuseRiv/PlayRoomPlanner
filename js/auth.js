$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.text();
        
        $btn.prop('disabled', true).text('Accesso in corso...');
        $('#loginFeedback').empty();

        $.ajax({
            url: 'backend/login_process.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'index.php?page=dashboard';
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text(originalText);
                let message = "Errore di connessione.";
                try {
                    let res = JSON.parse(xhr.responseText);
                    message = res.message;
                } catch(e) {}
                $('#loginFeedback').html(`<div class="alert alert-danger">${message}</div>`);
            }
        });
    });
});