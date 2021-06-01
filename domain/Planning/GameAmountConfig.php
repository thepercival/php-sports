<?php
declare(strict_types=1);

namespace Sports\Planning;

use Sports\Competition\Sport as CompetitionSport;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\GameMode;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;

class GameAmountConfig extends Identifiable
{
    public function __construct(
        protected CompetitionSport $competitionSport,
        protected RoundNumber $roundNumber,
        protected int $amount,
        protected int $nrOfGamesPerPlace
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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getNrOfGamesPerPlace(): int
    {
        return $this->nrOfGamesPerPlace;
    }

    public function setNrOfGamesPerPlace(int $nrOfGamesPerPlace): void
    {
        $this->nrOfGamesPerPlace = $nrOfGamesPerPlace;
    }

    public function createVariant(): SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant
    {
        $competitionSport = $this->getCompetitionSport();
        if ($competitionSport->getGameMode() === GameMode::SINGLE) {
            return new SingleSportVariant($competitionSport->getNrOfGamePlaces(), $this->getAmount());
        }
        if ($competitionSport->getGameMode() === GameMode::ALL_IN_ONE_GAME) {
            return new AllInOneGameSportVariant($this->getAmount());
        }
        return new AgainstSportVariant(
            $competitionSport->getNrOfHomePlaces(),
            $competitionSport->getNrOfAwayPlaces(),
            $this->getAmount(),
            $this->getNrOfGamesPerPlace(),
        );
    }
}
