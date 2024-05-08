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

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Exception;
use JsonException;
use League\Uri\Http;
use function Amp\async;
use function Amp\Future\await;
use function array_map;
use function array_merge;
use function http_build_query;
use function json_decode;
use function json_encode;
use function range;
use function sprintf;
use const JSON_THROW_ON_ERROR;

abstract class AbstractClient
{
    public function __construct(private readonly HttpClient $httpClient)
    {
    }

    /**
     * @param string $endpoint
     * @param int $expectStatus
     * @param string $method
     * @param array|null $query
     * @param array $data
     * @return array
     * @throws HttpException
     * @throws BufferException
     * @throws StreamException
     * @throws JsonException
     */
    public function query(
        string $endpoint,
        int $expectStatus = 200,
        string $method = 'GET',
        ?array $query = [],
        array $data = []
    ): array {
        $uri = (Http::new())->withPath($endpoint);

        if (!empty($query)) {
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
            return json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR)['data'] ?? [];
        }

        return [];
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @param int $maxRequests
     * @return array
     * @throws BufferException
     * @throws HttpException
     * @throws JsonException
     * @throws StreamException
     */
    public function getAll(string $endpoint, array $query = [], int $maxRequests = 10): array
    {
        $uri = (Http::new())->withPath($endpoint);

        $uri = $uri->withQuery(http_build_query(['page' => 1, 'per_page' => 100] + $query));

        $response = $this->httpClient->request(
            new Request($uri)
        );

        if ($response->getStatus() !== 200) {
            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()));
        }

        $bodyData = $response->getBody()->buffer();

        if ($bodyData) {
            $firstPage = json_decode($bodyData, associative: true, flags: JSON_THROW_ON_ERROR);

            try {
                $currentPage = (int)$firstPage['meta']['current_page'] ?? 1;
                $lastPage = (int)$firstPage['meta']['last_page'] ?? 1;
                $responses = await(array_map(function (int $page) use ($uri, $query) {
                    $uri = $uri->withQuery(http_build_query(['page' => $page, 'per_page' => 100] + $query));
                    return async(fn() => $this->httpClient->request(new Request($uri, 'GET')));
                }, $lastPage > $currentPage ? range($currentPage + 1, min($lastPage, $currentPage + $maxRequests + 1)) : []));

                return array_merge(
                    $firstPage['data'] ?? [],
                    ...array_map(function (Response $response) {
                        if ($response->getStatus() !== 200) {
                            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()));
                        }
                        return json_decode($response->getBody()->buffer(), associative: true, flags: JSON_THROW_ON_ERROR)['data'] ?? [];
                    }, $responses)
                );
            } catch (Exception $e) {
                throw new HttpException($e->getMessage(), previous: $e);
            }
        }

        return [];
    }
}