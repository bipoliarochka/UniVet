<?php

define('DEBUG', false); // Set to false on production

define('MAIL_SMTP', false); // Use SMTP, if "false" use "mail" and it settings
define('MAIL_SMTP_HOST', 'smtp.gmail.com'); // (Only if you use "MAIL_SMTP == true") SMTP server
define('MAIL_SMTP_PORT', 465); // (ТOnly if you use "MAIL_SMTP == true")  port
define('MAIL_SMTP_USERNAME', 'test@gmail.com'); // (Only if you use "MAIL_SMTP == true")  user
define('MAIL_SMTP_PASSWORD', 'secret'); // (Only if you use "MAIL_SMTP == true")  password

define('MAIL_SUBJECT', 'Сообщение с сайта - Унивет'); // Mail Subject
define('MAIL_TO_EMAIL', 'info@univet.ru'); // on which mail must be send mail

define('MAIL_MESSAGE_SUCCESS', 'Спасибо что связались с нами!'); // Message from contact form when mail is succesfull send.
define('MAIL_MESSAGE_ERROR', 'Сообщение не отправлено, попробуйте позднее');  // Message from contact form when mail is not send when send is failed.

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

require_once __DIR__ . '/libs/Swift/lib/swift_required.php';

/**
 * Parse mail template
 *
 * @param string $name
 * @param string $email
 * @param string $message
 * @param string $type
 * @return string
 */
function mail_content_layout ($name, $email, $message, $type = 'html') {
    ob_start();
    require_once __DIR__.'/mail/template.' . $type;
    $output = ob_get_contents();
    ob_end_clean();
    return str_replace(array('{{name}}', '{{email}}', '{{message}}'), array($name, $email, nl2br($message)), $output);
}

/**
 * @param string $name
 * @param mix $default
 */
function post_param($name, $default = null) {
    return isset($_POST[$name]) ? $_POST[$name] : $default;
}
if (empty($bezspama)) {
if ($_POST) {
    $name = htmlentities(strip_tags(post_param('name', '')));
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlentities(strip_tags(post_param('message', '')));
    $errors = array();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Неверный email';
    }

    if (empty($name)) {
        $errors['name'] = 'Имя не может быть пустым';
    }


    if (empty($message)) {
        $errors['message'] = 'Сообщение не может быть пустым';
    }

    if (count($errors) === 0) {
        try {
            if (MAIL_SMTP) {
                $transport = Swift_SmtpTransport::newInstance(MAIL_SMTP_HOST, MAIL_SMTP_PORT, 'ssl')
                    ->setUsername(MAIL_SMTP_USERNAME)
                    ->setPassword(MAIL_SMTP_PASSWORD);
                $mailer = Swift_Mailer::newInstance($transport);
            } else {
                $transport = Swift_MailTransport::newInstance();
                $mailer = Swift_Mailer::newInstance($transport);
            }

            $message = Swift_Message::newInstance(MAIL_SUBJECT)
                ->setFrom(array($email => $name))
                ->setTo(array(MAIL_TO_EMAIL))
                ->setBody(mail_content_layout($name, $email, $message), 'text/html')
                ->setBody(mail_content_layout($name, $email, $message, 'txt'), 'text/plain');
            $result = $mailer->send($message);

            if ($result) {
                echo json_encode(array(
                    'success' => MAIL_MESSAGE_SUCCESS
                ));
            } else {
                echo json_encode(array(
                    'error' => MAIL_MESSAGE_ERROR
                ));
            }
        } catch (Swift_TransportException $e) {
            echo json_encode(array(
                'error' => DEBUG ? $e->getMessage() : 'Mail send transport error, please try again.'
            ));
        } catch (Exception $e) {
            echo json_encode(array(
                'error' => DEBUG ? $e->getMessage() : 'An error has occurred, please try again.'
            ));
        }
    } else {
        echo json_encode(compact('errors'));
    }
} else {
    header('HTTP/1.0 400 Bad Request', true, 400);
}
}
exit;
?>
