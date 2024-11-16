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
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Exception;
use Generator;
use JsonException;
use League\Uri\Http;
use Psr\Log\LoggerInterface;
use function Amp\async;
use function Amp\Future\await;
use function array_map;
use function array_merge;
use function http_build_query;
use function json_decode;
use function json_encode;
use function ksort;
use function min;
use function range;
use function sprintf;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class AbstractClient
{
    public const DEFAULT_PER_PAGE = 250;
    public const DEFAULT_MAX_PAGES = 50;

    public function __construct(private readonly HttpClient $httpClient, private readonly ?LoggerInterface $logger = null)
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
        int    $expectStatus = 200,
        string $method = 'GET',
        ?array $query = [],
        array  $data = [],
        ?string $unwrapKey = 'data'
    ): array
    {
        $uri = (Http::new())->withPath($endpoint);

        if (!empty($query)) {
            $uri = $uri->withQuery(http_build_query($query));
        }

        $response = $this->httpClient->request(
            new Request($uri, $method, $data ? BufferedContent::fromString(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) : '')
        );

        if ($response->getStatus() !== $expectStatus) {
            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()), response: $response);
        }

        $data = $response->getBody()->buffer();

        if ($data) {
            return (null !== $unwrapKey) ? (json_decode($data, associative: true, flags: JSON_THROW_ON_ERROR)[$unwrapKey] ?? []) : [$data];
        }

        return [];
    }

    public function items(string $endpoint, array $query = [], array $headers = [], string $iterateKey = 'data', ?int $maxPages = self::DEFAULT_MAX_PAGES): Generator
    {
        $uri = (Http::new())->withPath($endpoint);
        $currentPage = 0;
        do {
            $currentPage++;
            $uri = $uri->withQuery(http_build_query(['per_page' => static::DEFAULT_PER_PAGE] + $query + ['page' => $currentPage]));
            $response = $this->httpClient->request(
                new Request($uri)
            );

            if ($response->getStatus() !== 200) {
                throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()), response: $response);
            }

            $pageData = json_decode($response->getBody()->buffer(), associative: true, flags: JSON_THROW_ON_ERROR);

            $lastPage = (int)$pageData['meta']['last_page'] ?? 1;
            $currentPage = (int)$pageData['meta']['current_page'] ?? 1;
            foreach ($pageData[$iterateKey] as $item) {
                yield $item;
            }
        } while ($currentPage < (($maxPages === null) ? $lastPage : min($lastPage, $maxPages)));

        if ($maxPages !== null && $currentPage < $lastPage) {
            $this->logger?->debug('Hard limit reached, stopping iteration with {amount} leftover pages', ['amount' => $lastPage - $currentPage]);
        }
    }
}
