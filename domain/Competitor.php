<?php

declare(strict_types=1);

namespace Sports;

use Sports\Competitor\StartLocationInterface;

interface Competitor extends StartLocationInterface
{
    public function getName(): string;

    public function getPresent(): bool;

    public function getPublicInfo(): ?string;
    public function getPrivateInfo(): ?string;

    public function getCompetition(): Competition;
}
