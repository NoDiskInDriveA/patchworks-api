<?php

declare(strict_types=1);

namespace Nodiskindrivea\PatchworksApi\Types;

enum FlowRunStatus: int
{
    case ANY = 0;
    case RUNNING = 1;
    case SUCCESS = 2;
    case FAILED = 3;
    case STOPPED = 4;

    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
