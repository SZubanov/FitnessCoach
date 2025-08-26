@extends('adminlte::auth.login')

<div class="login-method">
    <h3>Войти через Telegram</h3>
    <script async src="https://telegram.org/js/telegram-widget.js?22"
            data-telegram-login="MyFitnessProgressBot"
            data-size="large"
            data-auth-url="{{ ENV('TELEGRAM_WEBHOOK_URL') }}"
            data-request-access="write">
    </script>
</div>
