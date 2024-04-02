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

use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use League\Uri\Http;
use function http_build_query;
use function json_decode;
use function json_encode;
use function sprintf;
use const JSON_THROW_ON_ERROR;

abstract class AbstractClient
{
    public function __construct(private HttpClient $httpClient)
    {
    }

    protected function query(string $endpoint, int $expectStatus = 200, ?string $method = 'GET', ?array $query = null, ?array $data = null): array
    {
        $uri = (Http::new())->withPath($endpoint);

        if ($query) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        $response = $this->httpClient->request(
            new Request($uri, $method, $data ? BufferedContent::fromString(json_encode($data, JSON_THROW_ON_ERROR)) : '')
        );

        if ($response->getStatus() !== $expectStatus) {
            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()));
        }

        $data = $response->getBody()->buffer();

        if ($data) {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }

        return [];
    }
}