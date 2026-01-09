<?php

declare(strict_types=1);

namespace Sports\Planning;

use Sports\Competition\CompetitionSport;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

final class GameAmountConfig extends Identifiable
{
    protected int $amount = 1;

    public function __construct(
        protected CompetitionSport $competitionSport,
        protected RoundNumber $roundNumber,
        protected int $nrOfCycles,
        protected int $nrOfCycleParts
    ) {
        $this->roundNumber->getGameAmountConfigs()->add($this);
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }

    public function createSport(): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport
    {
        return $this->getCompetitionSport()->createSport();
    }

    public function getNrOfCycles(): int
    {
        return $this->nrOfCycles;
    }

    public function getNrOfCycleParts(): int
    {
        return $this->nrOfCycleParts;
    }
}
