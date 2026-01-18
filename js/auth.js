$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'backend/login_process.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    window.location.href = 'index.php?page=dashboard';
                }
            },
            error: function(xhr) {
                let res = JSON.parse(xhr.responseText);
                $('#loginFeedback').html(`<div class="alert alert-danger">${res.message}</div>`);
            }
        });
    });
});