<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\jwt;

use Firebase\JWT\JWT;

final readonly class JWTFactory
{
    public function __construct(private string $privateKeyPath) {}

    public function createToken(array $payload, $expirationTime = 86400, $alg = 'RS256'): string
    {
        $payload['iat'] = time();
        $payload['exp'] = $payload['iat'] + $expirationTime;

        return JWT::encode($payload, file_get_contents('../' . $this->privateKeyPath), $alg);
    }
}
