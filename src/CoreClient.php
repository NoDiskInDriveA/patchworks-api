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

use DateTimeInterface;
use Generator;
use function array_filter;
use function join;

class CoreClient extends AbstractClient
{
    public function getScripts(): Items
    {
        return $this->items('scripts', ['include' => 'versions,latestVersion']);
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

    public function updateScript(string $scriptId, array $props): array
    {
        return $this->query('scripts/' . $scriptId, 200, 'PATCH', null, $props);
    }

    public function createScript(array $props): array
    {
        return $this->query('scripts', 201, 'POST', null, $props);
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

    public function getDataPools(): Items
    {
        return $this->items('data-pool');
    }

    public function getDataPool(int $id): array
    {
        return $this->query('data-pool/' . $id, 200, 'GET');
    }

    public function updateDataPool(int $id, array $props): array
    {
        return $this->query('data-pool/' . $id, 200, 'PATCH', null, $props);
    }

    public function createDataPool(array $props): array
    {
        return $this->query('data-pool', 201, 'POST', null, $props);
    }

    public function deleteDataPool(int $id): array
    {
        return $this->query('data-pool/' . $id, 200, 'DELETE');
    }

    public function getDataPoolContent(int $id): Items
    {
        return $this->items('data-pool/' . $id . '/deduped-data');
    }

    public function retryRun(string $id): array
    {
        return $this->query('flow-runs/' . $id . '/retry', method: 'POST');
    }

    public function getFlowRun(string $id): array
    {
        return $this->query('flow-runs/' . $id, method: 'GET', query: ['include' => 'flow,flowVersion']);
    }

    public function searchFlowRuns(
        DateTimeInterface  $filterStartedAfter,
        ?DateTimeInterface $filterStartedBefore = null,
        ?string            $filterStatus = null,
        ?string            $filterSearch = null,
        ?int               $filterFlowId = null,
        ?int               $filterFlowVersionId = null,
        ?string            $filterTrigger = null,
        bool               $filterHasWarnings = false,
        bool               $filterIsRetried = false,
        string             $sortBy = '-started_at'
    ): Items
    {
        // unused filters: user_id, started_on
        $query = [
            'include' => 'flow,flowVersion,payloadSize,retriedFlowRun',
            'fields[flow]' => 'id,name',
            'sort' => $sortBy,
            'filter[started_after]' => $filterStartedAfter->getTimestamp() * 1000,
        ];

        if (null !== $filterStartedBefore) {
            $query['filter[started_before]'] = $filterStartedBefore->getTimestamp() * 1000;
        }

        if ($filterIsRetried) {
            $query['filter[retried]'] = 'true';
        }

        if ($filterHasWarnings) {
            $query['filter[has_warnings]'] = 'true';
        }

        foreach (array_filter([
            'status' => $filterStatus,
            'search' => $filterSearch,
            'flow_id' => $filterFlowId,
            'flow_version_id' => $filterFlowVersionId,
            'trigger' => $filterTrigger,
        ], fn($v) => null !== $v) as $k => $v) {
            $query['filter[' . $k . ']'] = $v;
        }

        return $this->items('flow-runs', $query);
    }

    public function getFlowRunLogs(string $flowRunId, string $sortBy = 'created_at'): Items
    {
        $query = [
            'include' => 'flowRunLogMetadata',
            'sort' => $sortBy,
            'load_payload_ids' => 'true',
        ];

        return $this->items(join('/', ['flow-runs', $flowRunId, 'flow-run-logs']), $query);
    }

    public function getPayloadMetadata(string $flowRunId, string $flowStepId): Items
    {
        // TODO support flow_log_id filter?
        $query = [
            'filter[flow_run_id]' => $flowRunId,
            'filter[flow_step_id]' => $flowStepId,
        ];

        return $this->items(join('/', ['payload-metadata']), $query);
    }

    public function getPayload(int $payloadId): ?string
    {
        $result = $this->query(
            join('/', ['payload-metadata', $payloadId, 'download']),
            unwrapKey: null
        );

        return $result[0] ?? null;
    }

    public function getScheduledFlows(?string $status = null): Items
    {
        $query = [
            'include' => 'flow,flowVersion,payloadMetadata',
        ];

        if (null !== $status) {
            $query['filter[status]'] = $status;
        }

        return $this->items(join('/', ['scheduled-flows']), $query);
    }

    public function deleteScheduledFlow(string $id): array
    {
        return $this->query('scheduled-flows/' . $id, 200, 'DELETE');
    }

    public function getFlowSteps(int $flowVersionId): array
    {
        $query = [
            'to_tree' => true,
            'include' => 'endpoint.system.logo,connector,filters,parentFlowStep,routes,variables,scriptVersion.script,flowVersion,cache,flow,notificationGroup',
            'load_notes_count' => false,
        ];

        return $this->query(sprintf('flow-versions/%s/steps', $flowVersionId), query: $query);
    }
}
