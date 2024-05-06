<?php

declare(strict_types=1);

namespace Nodiskindrivea\PatchworksApi\Types;

enum PoolEntity: int
{
    case DATA = 1;
    case ORDER = 37;

    /**
     * @return string
     */
    public function title(): string
    {
        return match ($this) {
            self::DATA => 'Data',
            self::ORDER => 'Order',
            default => 'Unknown'
        };
    }
}
