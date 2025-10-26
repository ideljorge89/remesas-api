<?php

declare(strict_types=1);

namespace App\Swagger;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class SwaggerDecorator implements NormalizerInterface
{
    /**
     * @var NormalizerInterface
     */
    private $decorated;

    public function __construct(NormalizerInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * @param mixed $data
     * @param string $format
     * @return bool
     */
    public function supportsNormalization($data, $format = null): bool
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    /**
     * @param mixed $object
     * @param string $format
     * @param array $context
     * @return array|\ArrayObject|bool|float|int|string|null
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $docs = $this->decorated->normalize($object, $format, $context);
        /*dump($docs);
        exit;*/
        $docs['components']['schemas']['Token'] = [
            'type' => 'object',
            'properties' => [
                'access_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'refresh_token' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
                'refresh_token_ttl' => [
                    'type' => 'integer',
                    'readOnly' => true,
                ],
                'refresh_token_expire_in' => [
                    'type' => 'string',
                    'readOnly' => true,
                ],
            ],
        ];
        $docs['components']['schemas']['RemesasRegistradas'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'readOnly' => true,
                    'example' => true
                ],
                'msg' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Remesas registradas satisfactoriamente. Total correctas 0"
                ],
                'fallidas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ]
                    ],
                    'examples' => [["dasdasd", "dasdasd", "dasdasd"], ["dasdasd", "dasdasd", "dasdasd"]]
                ],
            ],
        ];
        $docs['components']['schemas']['TransferenciasRegistradas'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'readOnly' => true,
                    'example' => true
                ],
                'msg' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Transferencias registradas satisfactoriamente. Total correctas 0"
                ],
                'fallidas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                        ]
                    ],
                    'examples' => [["dasdasd", "dasdasd", "dasdasd"], ["dasdasd", "dasdasd", "dasdasd"]]
                ],
            ],
        ];

        $docs['components']['schemas']['TransferenciaUpdate'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'readOnly' => true,
                    'example' => true
                ],
                'msg' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Actualización de las transferencias', 'correctas' => [], 'fallidas' => []"
                ],
                'fallidas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'msg' => 'No se ha registrado una transferencia con la referencia 123456, verifique'
                        ]
                    ],
                    'examples' => [["dasdasd"], ["dasdasd"]]
                ],
            ],
        ];

        $docs['components']['schemas']['RemesaUpdate'] = [
            'type' => 'object',
            'properties' => [
                'success' => [
                    'type' => 'boolean',
                    'readOnly' => true,
                    'example' => true
                ],
                'msg' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Actualización de las facturas', 'correctas' => [], 'fallidas' => []"
                ],
                'fallidas' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'string',
                            'msg' => 'No se ha registrado una factura con la referencia 123456, verifique.'
                        ]
                    ],
                    'examples' => [["dasdasd"], ["dasdasd"]]
                ],
            ],
        ];

        $docs['components']['schemas']['TokenNotFound'] = [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 401
                ], 'message' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Invalid credentials."
                ],
            ],
        ];
        $docs['components']['schemas']['TokenExpired'] = [
            'type' => 'object',
            'properties' => [
                'code' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => 401
                ], 'message' => [
                    'type' => 'string',
                    'readOnly' => true,
                    'example' => "Expired JWT Token"
                ],
            ],
        ];
        $examples = [];
        $examples[] = [
            "Referencia" => "5243123",
            "Nombre" => "asd",
            "Apellido1" => "asd",
            "Apellido2" => "dasdasd",
            "Direccion" => "dasdasd",
            "Provincia" => "asd",
            "Municipio" => "Hoasdasdlguin",
            "Telefono" => "asdasd",
            "Monto" => 500,
            "Moneda" => "USD",
            "Nota" => ""
        ];
        $examples[] = [
            "Referencia" => "3123123",
            "Nombre" => "asd",
            "Apellido1" => "asd",
            "Apellido2" => "dasdasd",
            "Direccion" => "dasdasd",
            "Provincia" => "asd",
            "Municipio" => "Hoasdasdlguin",
            "Telefono" => "asdasd",
            "Monto" => 500,
            "Moneda" => "USD",
            "Nota" => ""
        ];

        $examplesT = [];
        $examplesT[] = [
            "Referencia" => "123456",
            "Emisor" => "asdasds",
            "TitularTarjeta" => "asdsad",
            "NumeroTarjeta" => "9225 XXXX XXXX XXXX",
            "Monto" => 250,
            "Moneda" => "USD",
            "Nota" => "asdasdsad",
        ];
        $examplesT[] = [
            "Referencia" => "123456",
            "Emisor" => "asdasds",
            "TitularTarjeta" => "asdsad",
            "NumeroTarjeta" => "9225 XXXX XXXX XXXX",
            "Monto" => 250,
            "Moneda" => "USD",
            "Nota" => "asdasdsad",
        ];

        $examplesU = [];
        $examplesU[] = [
            "Referencia" => "123456",
        ];
        $examplesU[] = [
            "Referencia" => "123456",
        ];

        $docs['definitions']["Remesa"]["additionalProperties"] = false;
        $docs['paths']['/api/remesas-bulk']['post']['parameters'] = [[
            "name" => "remesa",
            "in" => "body",
            "description" => "The new Remesa resource",
            "schema" => [
                'type' => 'array',
                'items' => [
                    "type" => "object",
                    '$ref' => "#/definitions/Remesa",

                ],
                "example" => $examples
            ]
        ]];

        $docs['definitions']["Transferencia"]["additionalProperties"] = false;
        $docs['paths']['/api/transferencias-bulk']['post']['parameters'] = [[
            "name" => "transferencia",
            "in" => "body",
            "description" => "The new Transferencia resource",
            "schema" => [
                'type' => 'array',
                'items' => [
                    "type" => "object",
                    '$ref' => "#/definitions/Transferencia",

                ],
                "example" => $examplesT
            ]
        ]];

        $docs['definitions']["TransferenciaUpdate"]["additionalProperties"] = false;
        $docs['paths']['/api/search-transferencias-update']['post']['parameters'] = [[
            "name" => "transferencia",
            "in" => "body",
            "description" => "Update Transferencia resource",
            "schema" => [
                'type' => 'array',
                'items' => [
                    "type" => "object",
                    '$ref' => "#/definitions/TransferenciaUpdate",

                ],
                "example" => $examplesU
            ]
        ]];

        $docs['definitions']["RemesaUpdate"]["additionalProperties"] = false;
        $docs['paths']['/api/search-remesas-update']['post']['parameters'] = [[
            "name" => "transferencia",
            "in" => "body",
            "description" => "Update Remesas resource",
            "schema" => [
                'type' => 'array',
                'items' => [
                    "type" => "object",
                    '$ref' => "#/definitions/RemesaUpdate",

                ],
                "example" => $examplesU
            ]
        ]];

        $docs['paths']['/api/remesas-bulk']['post']['responses'] = [
            Response::HTTP_OK => [
                'description' => 'Remesas registradas',
                'schema' => [
                    '$ref' => '#/components/schemas/RemesasRegistradas',
                ]
            ],
            401 => [
                'description' => 'Token Expired',
                'schema' => [
                    '$ref' => '#/components/schemas/TokenExpired'
                ]
            ],
        ];

        $docs['paths']['/api/transferencias-bulk']['post']['responses'] = [
            Response::HTTP_OK => [
                'description' => 'Transferencias registradas',
                'schema' => [
                    '$ref' => '#/components/schemas/TransferenciasRegistradas',
                ]
            ],
            401 => [
                'description' => 'Token Expired',
                'schema' => [
                    '$ref' => '#/components/schemas/TokenExpired'
                ]
            ],
        ];

        $docs['paths']['/api/search-transferencias-update']['post']['responses'] = [
            Response::HTTP_OK => [
                'description' => 'Actualización de las transferencias',
                'schema' => [
                    '$ref' => '#/components/schemas/TransferenciaUpdate',
                ]
            ],
            401 => [
                'description' => 'Token Expired',
                'schema' => [
                    '$ref' => '#/components/schemas/TokenExpired'
                ]
            ],
        ];


        $docs['paths']['/api/search-remesas-update']['post']['responses'] = [
            Response::HTTP_OK => [
                'description' => 'Actualización de las remesas',
                'schema' => [
                    '$ref' => '#/components/schemas/RemesaUpdate',
                ]
            ],
            401 => [
                'description' => 'Token Expired',
                'schema' => [
                    '$ref' => '#/components/schemas/TokenExpired'
                ]
            ],
        ];


        $docs['components']['schemas']['Credentials'] = [
            'type' => 'object',
            'properties' => [
                'username' => [
                    'type' => 'string',
                    'example' => 'api',
                ],
                'password' => [
                    'type' => 'string',
                    'example' => 'api',
                ],
            ],
        ];

        $tokenDocumentation = [
            'paths' => [
                '/api/token' => [
                    'post' => [
                        'tags' => ['Token'],
                        'operationId' => 'postCredentialsItem',
                        'summary' => 'Get JWT token to login.',
                        'parameters' => [
                            [
                                'description' => 'Create new JWT Token',
                                'in' => "body",
                                'schema' => [
                                    '$ref' => '#/components/schemas/Credentials',
                                ]
                            ]
                        ],
                        'responses' => [
                            Response::HTTP_OK => [
                                'description' => 'Access token',
                                'schema' => [
                                    '$ref' => '#/components/schemas/Token',
                                ]
                            ],
                            401 => [
                                'description' => 'Token not found',
                                'schema' => [
                                    '$ref' => '#/components/schemas/TokenNotFound'
                                ]
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return array_merge_recursive($docs, $tokenDocumentation);
    }
}