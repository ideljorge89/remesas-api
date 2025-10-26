<?php

namespace App\Form\Type;

use App\Entity\NmEstado;
use App\Entity\NmEstadoTransferencia;
use App\Entity\TnFactura;
use App\Entity\TnReporteTransferencia;
use App\Entity\TnTransferencia;
use App\Model\AsignarTransferenciaModel;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class PendientesAsignarType extends AbstractType
{
    private $container;
    private $entityManager;
    private $security;
    private $user;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine')->getManager();
        $this->security = $this->container->get('security.authorization_checker');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();

        $builder
            ->add('transferencias', CollectionType::class, array(
                'translation_domain' => 'AppBundle',
                'entry_type' => TransferenciaRepartidorType::class,
                'label' => 'backend.transferencia.asignar.repartidor',
                'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AsignarTransferenciaModel::class,
        ]);
    }
}
