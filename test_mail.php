<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'alejandromzar06@gmail.com'; 
    $mail->Password   = 'uqwp dpxb vfnr rvel';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Destinatarios
    $mail->setFrom('alejandromzar06@gmail.com', 'Sistema DisciplinarMx');
    $mail->addAddress('zargdev@gmail.com'); 

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Correo de prueba PHPMailer';
    $mail->Body    = '<b>¡Hola! Todo funciona correctamente 🎉</b>';

    $mail->send();
    echo 'Correo enviado correctamente';
} catch (Exception $e) {
    echo "Error al enviar: {$mail->ErrorInfo}";
}
