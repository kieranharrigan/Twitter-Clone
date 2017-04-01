<?php
require('recaptcha/src/autoload.php');
$secret = '6LffjRYUAAAAAKCjUjw4zvCjbEXjXyRhDXHBBqFm';

if(isset($_POST['g-recaptcha-response'])):
    $recaptcha = new \ReCaptcha\ReCaptcha($secret);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

    if($resp->isSuccess()):
        echo 'reCAPTCHA completed successfully.';
    else:
        echo 'Please complete the captcha above.';
    endif;
endif;
?>

