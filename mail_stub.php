<?php
function send_email_stub($to, $subject, $body) {
    // Для отладки — логировать, а не отправлять
    file_put_contents('emails.log', "To: $to\nSubject: $subject\n\n$body\n\n---\n", FILE_APPEND);
}
