<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 8/12/19
 * Time: 10:11
 */

namespace App\Session\Security\Exceptions;


use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ReCaptchaLoginException extends AuthenticationException
{
    /**
     * {@inheritdoc}
     */
    public function getMessageKey()
    {
        return 'recaptcha.login_form_captcha.invalid';
    }
}
