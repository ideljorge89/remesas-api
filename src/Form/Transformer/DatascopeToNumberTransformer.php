<?php

// src/Form/DataTransformer/IssueToNumberTransformer.php
namespace App\Form\Transformer;



use App\Entity\Datascope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DatascopeToNumberTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (hotel) to a string (number).
     *
     * @param  Datascope|null $issue
     * @return string
     */
    public function transform($datascope)
    {
        if (null === $datascope) {
            return '';
        }

        return $datascope->getId();
    }

    /**
     * Transforms a string (number) to an object (hotel).
     *
     * @param  string $issueNumber
     * @return Datascope|null
     * @throws TransformationFailedException if object (hotel) is not found.
     */
    public function reverseTransform($issueNumber)
    {
        // no issue number? It's optional, so that's ok
        if (!$issueNumber) {
            return;
        }

        $issue = $this->entityManager
            ->getRepository(Datascope::class)
            // query for the issue with this id
            ->find($issueNumber);

        if (null === $issue) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(sprintf(
                'An issue with number "%s" does not exist!',
                $issueNumber
            ));
        }

        return $issue;
    }
}