<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final readonly class JWTDecoder
{
    private Key $key;

    public function __construct(string $publicKeyPath, string $algorithm = 'RS256')
    {
        $this->key = new Key(file_get_contents('../' . $publicKeyPath), $algorithm);
    }

    public function decode(string $token): array
    {
        return (array)JWT::decode($token, $this->key);
    }
}
