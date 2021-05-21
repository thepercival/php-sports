<?php
declare(strict_types=1);

namespace Sports\Planning;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class GameAmountConfig extends Identifiable
{
    public function __construct(
        protected CompetitionSport $competitionSport,
        protected RoundNumber $roundNumber,
        protected int $amount,
        protected int $partial
    )
    {
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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getPartial(): int
    {
        return $this->partial;
    }

    public function setPartial(int $partial): void
    {
        $this->partial = $partial;
    }
}
