<?php

namespace FilmAnalogger\FilmAnaloggerApi\OpenApi;

class AuthenticationErrorResponse
{
    const RESPONSE_401 = [
        'description' => 'Unauthorized',
        'summary' => 'Token is not present in the request headers',
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => [
                            'type' => 'string',
                            'example' => 'Token is not present in the request headers',
                        ],
                    ],
                ],
            ],
        ],
    ];

    const RESPONSE_403 = [
        'description' => 'Forbidden',
        'summary' => 'You do not have sufficient rights to access the resource',
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'error' => [
                            'type' => 'string',
                            'example' => 'Forbidden',
                        ],
                        'exception' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => [
                                    'type' => 'string',
                                    'example' => 'Access Denied.',
                                ],
                                'code' => [
                                    'type' => 'integer',
                                    'example' => 403,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}
