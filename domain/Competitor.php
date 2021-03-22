<?php
declare(strict_types=1);

namespace Sports;

interface Competitor extends Place\Location
{
    public function getName(): string;
    public function getRegistered(): bool;
    public function getInfo(): ?string;
    public function getCompetition(): Competition;
}
