<?php

declare(strict_types=1);

namespace Sports\Team;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use League\Period\Period;
use Sports\Game\Against as AgainstGame;
use Sports\Game\Participation as GameParticipation;
use Sports\Person;
use Sports\Team;

class Player extends Role
{
    protected int|null $shirtNumber = null;
    protected int $line;
    /**
     * @var Collection<int|string, GameParticipation>
     */
    protected Collection $gameParticipations;

    public function __construct(Team $team, Person $person, Period $period, int $line)
    {
        parent::__construct($team, $person, $period);
        $this->setLine($line);
        if (!$person->getPlayers()->contains($this)) {
            $person->getPlayers()->add($this);
        }
        $this->gameParticipations = new ArrayCollection();
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function setLine(int $line): void
    {
        $this->line = $line;
    }

    public function getShirtNumber(): ?int
    {
        return $this->shirtNumber;
    }

    public function setShirtNumber(int $shirtNumber = null): void
    {
        $this->shirtNumber = $shirtNumber;
    }

    /**
     * @return Collection<int|string, GameParticipation>
     */
    public function getGameParticipations(): Collection
    {
        return $this->gameParticipations;
    }

    /**
     * @return array<int|string, AgainstGame>
     */
    public function getAgainstGames(Period|null $period = null): array
    {
        return array_filter(
            array_map(function(GameParticipation $gameParticipation): AgainstGame{
                return $gameParticipation->getAgainstGamePlace()->getGame();
            }, $this->gameParticipations->toArray())
        , function(AgainstGame $g) use ($period): bool {
                return $period === null || $period->contains($g->getStartDateTime());
        });
    }
}
