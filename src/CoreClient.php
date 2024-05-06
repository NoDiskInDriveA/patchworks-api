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

use function join;

class CoreClient extends AbstractClient
{
    public function getScripts(int $page = 1, $perPage = 100): array
    {
        return $this->query('scripts', 200, 'GET', ['page' => $page, 'per_page' => $perPage, 'include' => 'versions,latestVersion']);
    }

    public function getScript(string $scriptId): array
    {
        return $this->query(
            join('/', ['scripts', $scriptId]),
            200,
            'GET',
            ['include' => 'versions,latestVersion']
        );
    }

    public function getScriptVersion(string $scriptVersionId): array
    {
        return $this->query(
            join('/', ['script-versions', $scriptVersionId]),
            200,
            'GET',
            ['include' => 'content']
        );
    }

    public function updateScriptContent(string $scriptId, string $content): array
    {
        return $this->query(
            join('/', ['scripts', $scriptId, 'script-versions']),
            201,
            'POST',
            null,
            ['content' => $content],
        );
    }

    public function getDataPools(int $page = 1, $perPage = 100)
    {
        return $this->query('data-pool', 200, 'GET', ['page' => $page, 'per_page' => $perPage]);
    }

    public function getDataPool(int $id)
    {
        return $this->query('data-pool/' . $id, 200, 'GET');
    }

    public function updateDataPool(int $id, array $props)
    {
        return $this->query('data-pool/' . $id, 200, 'PATCH', null, $props);
    }

    public function createDataPool(array $props)
    {
        return $this->query('data-pool', 201, 'POST', null, $props);
    }

    public function deleteDataPool(int $id)
    {
        return $this->query('data-pool/' . $id, 200, 'DELETE');
    }

    public function getDataPoolContent(int $id, int $page = 1, $perPage = 100)
    {
        return $this->query('data-pool/' . $id . '/deduped-data', 200, 'GET', ['page' => $page, 'per_page' => $perPage]);
    }
}