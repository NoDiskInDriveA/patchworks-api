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

namespace Nodiskindrivea\PatchworksApi\Types;

use function strtolower;
use function ucwords;

enum PoolEntity: int
{
    case DATA = 1;
    case ORDER = 37;
    case PRODUCT = 54;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::DATA => 'Data',
            self::ORDER => 'Order',
            self::PRODUCT => 'Product',
            default => ucwords(strtolower($this->name))
        };
    }
}
