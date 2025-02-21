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

namespace Nodiskindrivea\PatchworksApi\Interceptor;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Cancellation;
use Amp\ForbidCloning;
use Amp\ForbidSerialization;
use Amp\Http\Client\ApplicationInterceptor;
use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use JsonException;
use Nodiskindrivea\PatchworksApi\Api\CredentialsInterface;
use Psr\Log\LoggerInterface;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class Authorization implements ApplicationInterceptor
{
    use ForbidCloning;
    use ForbidSerialization;

    private const AUTH_URL = 'https://svc-fabric.wearepatchworks.com/api/v1';

    public function __construct(private readonly CredentialsInterface $credentials, private readonly ?LoggerInterface $logger = null)
    {
    }

    /**
     * @param Request $request
     * @param Cancellation $cancellation
     * @param DelegateHttpClient $httpClient
     * @return Response
     * @throws HttpException
     * @throws BufferException
     * @throws StreamException
     * @throws JsonException
     */
    public function request(
        Request            $request,
        Cancellation       $cancellation,
        DelegateHttpClient $httpClient
    ): Response
    {
        $clonedRequest = clone $request;

        if ($this->credentials->token()) {
            $this->logger?->debug('Patchworks token found, using existing authentication.');
            $request->setHeader('Authorization', 'Bearer ' . $this->credentials->token());
            $originalResponse = $httpClient->request($request, $cancellation);

            if ($originalResponse->getStatus() !== 401) {
                return $originalResponse;
            }

            $this->logger?->debug('Existing authentication expired, reauthenticating.');
        } else {
            $this->logger?->debug('Patchworks unauthenticated, authenticating.');
        }

        $authClient = HttpClientBuilder::buildDefault();
        $authRequest = new Request(
            self::AUTH_URL . '/login',
            'POST',
            BufferedContent::fromString(json_encode([
                'email' => $this->credentials->username(),
                'password' => $this->credentials->password(),
            ], JSON_THROW_ON_ERROR))
        );
        $authRequest->setHeader('Content-Type', 'application/json');
        $authRequest->setHeader('Accept', 'application/json');

        $authResponse = $authClient->request($authRequest, $cancellation);

        if ($authResponse->getStatus() === 200) {
            $this->logger?->debug('Authentication successful.');
            $data = json_decode($authResponse->getBody()->buffer($cancellation), true, 512, JSON_THROW_ON_ERROR);
            $clonedRequest->setHeader('Authorization', 'Bearer ' . $this->credentials->token($data['token']));
            return $httpClient->request($clonedRequest, $cancellation);
        }

        throw new HttpException('Could not authenticate');
    }
}
