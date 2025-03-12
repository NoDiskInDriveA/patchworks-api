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
use Nodiskindrivea\PatchworksApi\Types\RequestOption;
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
        private readonly array            $options = [],
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
        if ($this->currentPage >= ((!isset($this->options[RequestOption::MAX_PAGES->value])) ? $this->lastPage : min($this->lastPage, $this->options[RequestOption::MAX_PAGES->value]))) {
            if (isset($this->options[RequestOption::MAX_PAGES->value]) && $this->currentPage < $this->lastPage) {
                $this->logger?->debug('Hard limit reached, stopping iteration with {amount} leftover pages', ['amount' => $this->lastPage - $this->currentPage]);
            }
            $this->items = null;
            return;
        }
        $this->currentPage++;
        $uri = (Http::new())->withPath($this->endpoint);

        $request = new Request($uri->withQuery(http_build_query(['per_page' => $this->options[RequestOption::ITEMS_PER_PAGE->value]] + $this->query + ['page' => $this->currentPage])));
        $request->setHeaders($this->headers);
        $request->setTransferTimeout($this->options[RequestOption::TRANSFER_TIMEOUT->value]);
        $request->setTcpConnectTimeout($this->options[RequestOption::CONNECT_TIMEOUT->value]);
        $request->setInactivityTimeout($this->options[RequestOption::INACTIVITY_TIMEOUT->value]);
        $this->limiter?->waitForSlot();
        $response = $this->httpClient->request(
            $request
        );

        if ($response->getStatus() !== 200) {
            throw new HttpException(sprintf('Unexpected response code %d for %s', $response->getStatus(), $response->getRequest()->getUri()), response: $response);
        }

        $pageData = json_decode($response->getBody()->buffer(), associative: true, flags: JSON_THROW_ON_ERROR);

        $this->currentPage = ((int)$pageData['meta']['current_page'] ?? 1);
        $this->lastPage = (int)$pageData['meta']['last_page'] ?? $this->currentPage;
        $this->count = (int)$pageData['meta']['total'] ?? 0;
        $this->items = $pageData[$this->iterateKey] ?? null;
    }

    public function count(): int
    {
        return $this->count;
    }
}
