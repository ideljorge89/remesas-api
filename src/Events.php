<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

/**
 * Contains all events thrown in the AppBundle
 */
final class Events
{
    /**
     ** The event listener method receives a App\Event\GetResponseEvent instance.
     */
    const NEW_USER_COMPLETED = 'user.event.new_user';

    /**
     ** The event listener method receives a App\Event\GetResponseEvent instance.
     */
    const EDIT_USER_COMPLETED = 'user.event.edit_user';

    /**
     ** The event listener method receives a App\Event\GetResponseEvent instance.
     */
    const NEW_USER_ERROR = 'user.event_error.new_user';

    /**
     ** The event listener method receives a App\Event\GetResponseEvent instance.
     */
    const EDIT_USER_ERROR = 'user.event_error.edit_user';

    /**
     ** The event listener method receives a App\Event\GetResponseEvent instance.
     */
    const DELETE_USER_COMPLETED = 'user.event.delete_user';
}
