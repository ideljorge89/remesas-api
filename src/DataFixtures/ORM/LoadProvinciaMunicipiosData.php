<?php

namespace App\DataFixtures\ORM;


use App\Entity\NmMunicipio;
use App\Entity\NmProvincia;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Parser;

class LoadProvinciaMunicipiosData extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $parser = new Parser();
        $data = $parser->parse(@file_get_contents('data/provincias-municipios.yml'));

        foreach ($data['data'] as $key => $value) {
            $category = new NmProvincia();
            $category->setName($key);
            $category->setAcronimo($value['acronimo']);
            $manager->persist($category);

            foreach ($value['municipios'] as $val) {
                $subCategory = new NmMunicipio();
                $subCategory->setProvincia($category);
                $subCategory->setName($val);
                $manager->persist($subCategory);
            }
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