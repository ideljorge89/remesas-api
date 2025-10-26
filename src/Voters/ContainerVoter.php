<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 18/02/20
 * Time: 18:17
 */

namespace App\Voters;

use App\Entity\Contenedor;
use App\Entity\TnUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class ContainerVoter extends Voter
{
    // these strings are just invented: you can use anything
    const VIEW = 'view';
    const EDIT = 'edit';
    const CANCEL = 'cancel';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        // if the attribute isn't one we support, return false
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::CANCEL])) {
            return false;
        }

        // only vote on Post objects inside this voter
        if (!$subject instanceof Contenedor) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof TnUser) {
            // the user must be logged in; if not, deny access
            return false;
        }

        // you know $subject is a Post object, thanks to supports
        /** @var Contenedor $post */
        $post = $subject;

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($post, $user);
            case self::EDIT:
                return $this->canEdit($post, $user);
            case self::CANCEL:
                return $this->canCancel($post, $user);
        }

        throw new \LogicException('This code should not be reached!');
    }

    private function canView(Contenedor $post, TnUser $user)
    {
        return true;
    }

    private function canEdit(Contenedor $contenedor, TnUser $user)
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        if ($this->security->isGranted('ROLE_COORDINADOR')) {
            if ($contenedor->getEstado() == Contenedor::ESTADO_REPORTADO) {
                return true;
            }
            return false;
        }

        if (in_array($contenedor->getEstado(), [Contenedor::ESTADO_REPORTADO, Contenedor::ESTADO_OFERTADO])) {
            return true;
        }
        return false;
    }


    private function canCancel(Contenedor $contenedor, TnUser $user)
    {
        // this assumes that the data object has a getOwner() method
        // to get the entity of the user who owns this data object
        if ($this->security->isGranted('ROLE_COORDINADOR')) {
            if ($contenedor->getEstado() == Contenedor::ESTADO_REPORTADO) {
                return true;
            }
            return false;
        }

        if (in_array($contenedor->getEstado(), [Contenedor::ESTADO_REPORTADO, Contenedor::ESTADO_OFERTADO])) {
            return true;
        }
        return false;
    }
}