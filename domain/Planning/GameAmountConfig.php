<?php

declare(strict_types=1);

namespace Sports\Planning;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\GameMode;
use SportsHelpers\Identifiable;
use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;

class GameAmountConfig extends Identifiable
{
    public function __construct(
        protected CompetitionSport $competitionSport,
        protected RoundNumber $roundNumber,
        protected int $amount,
    ) {
        $this->roundNumber->getGameAmountConfigs()->add($this);
    }

    public function getCompetitionSport(): CompetitionSport
    {
        return $this->competitionSport;
    }

    public function getCompetitionSportId(): string|int|null {
        return $this->competitionSport->getId();
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

    public function createVariant(): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|Single|AllInOneGame
    {
        $competitionSport = $this->getCompetitionSport();
        if ($competitionSport->getGameMode() === GameMode::Single) {
            return new Single($competitionSport->getNrOfGamePlaces(), $this->getAmount());
        }
        if ($competitionSport->getGameMode() === GameMode::AllInOneGame) {
            return new AllInOneGame($this->getAmount());
        }
        if( $competitionSport->getNrOfHomePlaces() === 1 && $competitionSport->getNrOfAwayPlaces() === 1 ) {
            return new AgainstOneVsOne($this->getAmount());
        }
        if( $competitionSport->getNrOfHomePlaces() === 1 && $competitionSport->getNrOfAwayPlaces() === 2 ) {
            return new AgainstOneVsTwo($this->getAmount());
        }
        if( $competitionSport->getNrOfHomePlaces() === 2 && $competitionSport->getNrOfAwayPlaces() === 2 ) {
            return new AgainstTwoVsTwo($this->getAmount());
        }
        throw new \Exception("incorrect nrOfGamePlaces for : " . $competitionSport );
    }
}
