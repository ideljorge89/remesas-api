<?php

namespace App\DataFixtures\ORM;


use App\Entity\NmEstado;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LoadEstadosData extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $estados = [
            ['nombre' => 'Pendiente', 'codigo' => '01'],
            ['nombre' => 'Aprobada', 'codigo' => '02'],
            ['nombre' => 'Distribucion', 'codigo' => '03'],
            ['nombre' => 'Entregada', 'codigo' => '04'],
            ['nombre' => 'Cancelada', 'codigo' => '05'],
        ];

        foreach ($estados as $value) {
            $estado = new NmEstado();
            $estado->setNombre($value['nombre']);
            $estado->setCodigo($value['codigo']);
            $manager->persist($estado);
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}