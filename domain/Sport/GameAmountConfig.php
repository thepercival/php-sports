<?php

namespace Sports\Sport;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport as SportBase;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;

class GameAmountConfig extends Identifiable
{
    protected CompetitionSport $competitionSport;
    protected RoundNumber $roundNumber;
    protected int $amount;

    public function __construct(CompetitionSport $competitionSport, RoundNumber $roundNumber, int $amount )
    {
        $this->competitionSport = $competitionSport;
        $this->roundNumber = $roundNumber;
        $this->roundNumber->getSportGameAmountConfigs()->add($this);
        $this->amount = $amount;
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

}
