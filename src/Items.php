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

namespace Nodiskindrivea\PatchworksApi;

use Amp\Http\Client\HttpClient;
use Amp\Http\Client\Request;
use Countable;
use IteratorAggregate;
use League\Uri\Http;
use Nodiskindrivea\PatchworksApi\Api\WaitingLimiter;
use Psr\Log\LoggerInterface;
use Traversable;
use function http_build_query;
use function json_decode;
use function min;
use function sprintf;
use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
class Items implements Countable, IteratorAggregate
{
    private ?int $count = null;
    private ?array $items = null;
    private int $currentPage = 0;
    private int $lastPage = 1;

    public function __construct(
        private readonly HttpClient       $httpClient,
        private readonly string           $endpoint,
        private readonly array            $query = [],
        private readonly array            $headers = [],
        private readonly string           $iterateKey = 'data',
        private readonly ?int             $itemsPerPage = 250,
        private readonly ?int             $maxPages = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?WaitingLimiter  $limiter = null,
    )
    {
        $this->nextPage();
    }

    public function getIterator(): Traversable
    {
        while ($this->items !== null) {
            yield from $this->items;
            $this->nextPage();
        }
    }

    private function nextPage(): void
    {
        if ($this->currentPage >= (($this->maxPages === null) ? $this->lastPage : min($this->lastPage, $this->maxPages))) {
            if ($this->maxPages !== null && $this->currentPage < $this->lastPage) {
                $this->logger?->debug('Hard limit reached, stopping iteration with {amount} leftover pages', ['amount' => $this->lastPage - $this->currentPage]);
            }
            $this->items = null;
            return;
        }

        $uri = (Http::new())->withPath($this->endpoint);

        $request = new Request($uri->withQuery(http_build_query(['per_page' => $this->itemsPerPage] + $this->query + ['page' => $this->currentPage])));
        $request->setHeaders($this->headers);

        $this->limiter?->waitForSlot();
        $response = $this->httpClient->request(
            $request
        );

        if ($response->getStatus() !== 200) {
            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()), response: $response);
        }

        $pageData = json_decode($response->getBody()->buffer(), associative: true, flags: JSON_THROW_ON_ERROR);

        $this->currentPage = (int)$pageData['meta']['current_page'] ?? 1;
        $this->lastPage = (int)$pageData['meta']['last_page'] ?? $this->currentPage;
        $this->count = (int)$pageData['meta']['total'] ?? 0;
        $this->items = $pageData[$this->iterateKey] ?? null;
    }

    public function count(): int
    {
        return $this->count;
    }
}
