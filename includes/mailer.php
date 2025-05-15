<?php

require_once __DIR__ . '/Exception.php'; 

require_once __DIR__ . '/PHPMailer.php'; 



function sendPasswordResetEmail($toEmail, $toName, $resetLink) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'localhost'; // Cambiar por tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'me@example.com'; // Cambiar por tu usuario SMTP
        $mail->Password = ''; // Cambiar por tu contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 25;
        
        // Remitente
        $mail->setFrom('no-reply@pequiven.com', 'PEQUIVEN');
        $mail->addAddress($toEmail, $toName);
        
        // Contenido
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña - PEQUIVEN';
        
        $mail->Body = "
            <h2>Recuperación de contraseña</h2>
            <p>Hola $toName,</p>
            <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
            <p>Por favor haz clic en el siguiente enlace para restablecer tu contraseña:</p>
            <p><a href=\"$resetLink\">$resetLink</a></p>
            <p>Este enlace expirará en 1 hora.</p>
            <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
            <p>Atentamente,<br>Equipo de PEQUIVEN</p>
        ";
        
        $mail->AltBody = "Hola $toName,\n\nHemos recibido una solicitud para restablecer tu contraseña.\n\nPor favor visita el siguiente enlace para restablecer tu contraseña:\n$resetLink\n\nEste enlace expirará en 1 hora.\n\nSi no solicitaste este cambio, puedes ignorar este mensaje.\n\nAtentamente,\nEquipo de PEQUIVEN";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
        return false;
    }
}
?>