<?php

declare(strict_types=1);

namespace Sports\Qualify;

enum QualifyTarget : string
{
    case Winners = 'W';
    case Dropouts = '';
    case Losers = 'L';

    public function getOpposing(): self
    {
        return $this === QualifyTarget::Winners ? QualifyTarget::Losers : QualifyTarget::Winners;
    }
}
