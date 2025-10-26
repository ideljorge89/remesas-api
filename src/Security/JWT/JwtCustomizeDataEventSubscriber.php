<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 16/12/19
 * Time: 14:31
 */

namespace App\Security\JWT;


use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class JwtCustomizeDataEventSubscriber implements EventSubscriberInterface
{

    private $requestStack;
    private $container;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack, ContainerInterface $container)
    {
        $this->requestStack = $requestStack;
        $this->container = $container;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::JWT_CREATED => ['onJWTCreated',0],
            Events::AUTHENTICATION_SUCCESS => ['onAuthenticationSuccessResponse',0]
        ];
    }

    /**
     * @param JWTCreatedEvent $event
     *
     * @return void
     */
    public function onJWTCreated(JWTCreatedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        $payload = $event->getData();
        $payload['ip'] = $request->getClientIp();


        $event->setData($payload);

        $header = $event->getHeader();
        $header['cty'] = 'JWT';

        $event->setData($payload);

        $event->setHeader($header);
    }

    /**
     * @param AuthenticationSuccessEvent $event
     */
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $data['access_token'] = $data['token'];
        unset($data['token']);
        $refr = new \DateTime('now');
        $refresh = $data['refresh_token'];
        unset($data['refresh_token']);
        $data['refresh_token'] = $refresh;
        $data['refresh_token_ttl'] = $this->container->getParameter('gesdinet_jwt_refresh_token.ttl');
        $refr->modify("+ " . $data['refresh_token_ttl'] . " seconds");
        $data['refresh_token_expire_in'] = $refr->format('Y-m-d H:i:s');

        $event->setData($data);
    }
}