<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Events;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlashListener implements EventSubscriberInterface
{
    private static $successMessages = array(
        Events::NEW_USER_COMPLETED => 'user.flash.new_user.success',
        Events::EDIT_USER_COMPLETED => 'user.flash.edit_user.success',
        Events::NEW_USER_ERROR => 'user.flash.new_user.error',
        Events::EDIT_USER_ERROR => 'user.flash.edit_user.error',
        Events::DELETE_USER_COMPLETED => 'user.flash.delete_user.success'
    );

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(SessionInterface $session, TranslatorInterface $translator)
    {
        $this->session = $session;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        $messages = array();
        foreach (self::$successMessages as $event => $message) {
            if (strpos($message, '.error') === FALSE) {
                $messages[$event] = 'addSuccessFlash';
            } else {
                $messages[$event] = 'addErrorFlash';
            }
        }
        return $messages;
    }

    public function addSuccessFlash(Event $event, $eventName = null)
    {
        // BC for SF < 2.4
        if (null === $eventName) {
            $eventName = $event->getName();
        }

        if (!isset(self::$successMessages[$eventName])) {
            /*throw new \InvalidArgumentException('This event does not correspond to a known flash message');*/
            return;
        }
        if ($this->session instanceof Session)
            $this->session->getFlashBag()->add('success', $this->trans(self::$successMessages[$eventName]));
    }

    private function trans($message, array $params = array())
    {
        return $this->translator->trans($message, $params, 'AppBundle');
    }

    public function addErrorFlash(Event $event, $eventName = null)
    {
        // BC for SF < 2.4
        if (null === $eventName) {
            $eventName = $event->getName();
        }

        if (!isset(self::$successMessages[$eventName])) {
            /*throw new \InvalidArgumentException('This event does not correspond to a known flash message');*/
            return;
        }
        if ($this->session instanceof Session)
            $this->session->getFlashBag()->add('error', $this->trans(self::$successMessages[$eventName]));
    }
}
