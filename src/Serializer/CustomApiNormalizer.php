<?php

namespace App\Serializer;

use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Model\Api\Remesa;
use App\Model\Api\RemesaUpdate;
use App\Model\Api\Transferencia;
use App\Model\Api\TransferenciaUpdate;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use \InvalidArgumentException;

class CustomApiNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     *
     * @var NormalizerInterface $normalizer
     */
    private $normalizer;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     *
     * @param NormalizerInterface $normalizer
     * @throws InvalidArgumentException
     */
    public function __construct(NormalizerInterface $normalizer, ValidatorInterface $validator)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The normalizer must implement the DenormalizerInterface');
        }

        $this->normalizer = $normalizer;
        $this->validator = $validator;
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize()
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (is_array($data) &&
            OperationType::COLLECTION === $context['operation_type']) {
            switch ($context) {
                case ('new_remesas_bulk' === $context['collection_operation_name'] &&
                    Remesa::class === $class):
                    $autorization = true;
                    break;
                case ('new_transferencias_bulk' === $context['collection_operation_name'] &&
                    Transferencia::class === $class):
                    $autorization = true;
                    break;
                case ('search_transferencias_update' === $context['collection_operation_name'] &&
                    TransferenciaUpdate::class === $class):
                    $autorization = true;
                    break;
                case ('search_remesas_update' === $context['collection_operation_name'] &&
                    RemesaUpdate::class === $class):
                    $autorization = true;
                    break;
                default:
                    $autorization = false;
                    break;
            }
            if ($autorization) {
                // bulk operation must update on POST as PUT is only allowed for item and not collection
                // https://api-platform.com/docs/core/operations
                $context['api_allow_update'] = true;
                $validator = $this->validator;
                return array_map(function ($item) use ($class, $format, $context, $validator) {
                    return $this->normalizer->denormalize($item, $class, $format, $context);
                }, $data);
            }
        }
        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return $this->normalizer->normalize($object, $format, $context);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Symfony\Component\Serializer\Normalizer\NormalizerInterface::supportsNormalization()
     */
    public function supportsNormalization($data, $format = null)
    {
        return $this->normalizer->supportsNormalization($data, $format);
    }
}