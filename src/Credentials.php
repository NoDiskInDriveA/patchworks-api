<?php
/*
 * Patchworks API client lib
 * Copyright (c) 2024 Patrick Durold
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Nodiskindrivea\PatchworksApi;

use Nodiskindrivea\PatchworksApi\Api\CredentialsInterface;

class Credentials implements CredentialsInterface
{
    private string $token = '';

    private function __construct(
        private readonly string $username,
        private readonly string $password,
    ) {
    }

    public static function create(
        string $username,
        string $password
    ): CredentialsInterface {
        return new static($username, $password);
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function token(?string $newToken = null): string
    {
        if (null !== $newToken) {
            $this->token = $newToken;
        }

        return $this->token;
    }
}
