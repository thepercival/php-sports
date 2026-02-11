<?php

declare(strict_types=1);

namespace Sports\Planning;

use Sports\Competition\CompetitionSport;
use Sports\Round\Number as RoundNumber;
use SportsHelpers\GameMode;
use SportsHelpers\Identifiable;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;

final class GameAmountConfig extends Identifiable
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
        return $this->competitionSport->id;
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

    public function createVariant(): Single|AgainstH2h|AgainstGpp|AllInOneGame
    {
        $competitionSport = $this->getCompetitionSport();
        if ($competitionSport->getGameMode() === GameMode::Single) {
            return new Single($competitionSport->getNrOfGamePlaces(), $this->getAmount());
        }
        if ($competitionSport->getGameMode() === GameMode::AllInOneGame) {
            return new AllInOneGame($this->getAmount());
        }
        if ($competitionSport->getNrOfH2H() > 0) {
            return new AgainstH2h(
                $competitionSport->getNrOfHomePlaces(),
                $competitionSport->getNrOfAwayPlaces(),
                $this->getAmount()
            );
        }
        return new AgainstGpp(
            $competitionSport->getNrOfHomePlaces(),
            $competitionSport->getNrOfAwayPlaces(),
            $this->getAmount()
        );
    }
}
