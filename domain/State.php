<?php

declare(strict_types=1);

namespace Sports;

class State // extends \SplEnum
{
    protected int $state;

    // const __default = self::Created;

    public const Created = 1;
    public const InProgress = 2;
    public const Finished = 4;
    public const Canceled = 8;

    public function __construct(int $state)
    {
        $this->state = $state;
    }

    public function getDescription(): string
    {
        if ($this->state === self::Created) {
            return "created";
        } elseif ($this->state === self::InProgress) {
            return "in progress";
        } elseif ($this->state === self::Finished) {
            return "finished";
        } elseif ($this->state === self::Canceled) {
            return "canceled";
        }
        throw new \Exception("unknown state", E_ERROR);
    }
}
