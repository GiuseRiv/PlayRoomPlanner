$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const email = $('#email').val();
        const password = $('#password').val();

        $.ajax({
            url: 'backend/login_process.php',
            type: 'POST',
            data: { email: email, password: password },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'frontend/dashboard.php';
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON ? xhr.responseJSON.message : "Errore durante l'accesso.";
                $('#loginFeedback').html('<div class="alert alert-danger">' + msg + '</div>');
            }
        });
    });
});