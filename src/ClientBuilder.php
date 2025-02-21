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

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Interceptor\ResolveBaseUri;
use Amp\Http\Client\Interceptor\SetRequestHeader;
use Nodiskindrivea\PatchworksApi\Api\CredentialsInterface;
use Nodiskindrivea\PatchworksApi\Api\WaitingLimiter;
use Nodiskindrivea\PatchworksApi\Interceptor\Authorization;
use Psr\Log\LoggerInterface;

final class ClientBuilder
{
    public function __construct(
        private CredentialsInterface $credentials,
        private ?WaitingLimiter $limiter,
        private ?LoggerInterface $logger,
    )
    {
    }

    public function getFabricClient(): FabricClient
    {
        return new FabricClient(
            (new HttpClientBuilder)
                ->intercept(new ResolveBaseUri(Api::FABRIC->value))
                ->intercept(new SetRequestHeader('Content-Type', 'application/json'))
                ->intercept(new SetRequestHeader('Accept', 'application/json'))
                ->intercept(new Authorization($this->credentials, $this->logger))
                ->build(),
            $this->logger,
            $this->limiter
        );
    }

    public function getCoreClient(): CoreClient
    {
        return new CoreClient(
            (new HttpClientBuilder)
                ->intercept(new ResolveBaseUri(Api::CORE->value))
                ->intercept(new SetRequestHeader('Content-Type', 'application/json'))
                ->intercept(new SetRequestHeader('Accept', 'application/json'))
                ->intercept(new Authorization($this->credentials, $this->logger))
                ->build(),
            $this->logger,
            $this->limiter
        );
    }
}
