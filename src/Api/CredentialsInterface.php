<?php

declare(strict_types=1);

namespace Nodiskindrivea\PatchworksApi\Api;

interface CredentialsInterface
{
    public function username(): string;

    public function password(): string;

    public function token(?string $newToken = null): string;
}