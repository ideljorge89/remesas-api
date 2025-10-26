<?php
/**
 * Created by PhpStorm.
 * User: raymond
 * Date: 17/01/20
 * Time: 5:47
 */

namespace App\Form\Extension;

use App\Form\Transformer\DatascopeToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatascopeSelectorType extends AbstractType
{

    private $transformer;

    public function __construct(DatascopeToNumberTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'invalid_message' => 'El reporte seleccionado no existe',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return HiddenType::class;
    }

    public function getName()
    {
        return 'datascope_selector_type';
    }
}