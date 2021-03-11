<?php

namespace Sports\Planning;

use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;
use SportsPlanning\SelfReferee;

class Config extends Identifiable
{
    protected RoundNumber $roundNumber;
    protected int $creationStrategy;
    protected bool $extension;
    protected bool $enableTime;
    protected int $minutesPerGame;
    protected int $minutesPerGameExt;
    protected int $minutesBetweenGames;
    protected int $minutesAfter;
    protected int $selfReferee;

    protected bool $teamupDep = false;
    protected int $nrOfHeadtoheadDep = 1;

    const DEFAULTEXTENSION = false;
    const DEFAULTENABLETIME = true;
    const DEFAULTGAMEAMOUNT = 1;

    public function __construct(RoundNumber $roundNumber)
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->setPlanningConfig($this);
    }

    public function getCreationStrategy(): int
    {
        return $this->creationStrategy;
    }

    public function setCreationStrategy(int $creationStrategy)
    {
        $this->creationStrategy = $creationStrategy;
    }

    public function getExtension(): bool
    {
        return $this->extension;
    }

    public function setExtension(bool $extension)
    {
        $this->extension = $extension;
    }

    public function getEnableTime(): bool
    {
        return $this->enableTime;
    }

    public function setEnableTime(bool $enableTime)
    {
        $this->enableTime = $enableTime;
    }

    public function getMinutesBetweenGames(): int
    {
        return $this->minutesBetweenGames;
    }

    public function setMinutesBetweenGames(int $minutesBetweenGames)
    {
        $this->minutesBetweenGames = $minutesBetweenGames;
    }

    public function getMinutesAfter(): int
    {
        return $this->minutesAfter;
    }

    public function setMinutesAfter(int $minutesAfter)
    {
        $this->minutesAfter = $minutesAfter;
    }

    public function getMaxNrOfMinutesPerGame(): int
    {
        return $this->getMinutesPerGame() + $this->getMinutesPerGameExt();
    }

    public function getMinutesPerGame(): int
    {
        return $this->minutesPerGame;
    }

    public function setMinutesPerGame(int $minutesPerGame)
    {
        $this->minutesPerGame = $minutesPerGame;
    }

    public function getMinutesPerGameExt(): int
    {
        return $this->minutesPerGameExt;
    }

    public function setMinutesPerGameExt(int $minutesPerGameExt)
    {
        $this->minutesPerGameExt = $minutesPerGameExt;
    }

    public function getSelfReferee(): int
    {
        return $this->selfReferee;
    }

    public function setSelfReferee(int $selfReferee)
    {
        $this->selfReferee = $selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== SelfReferee::DISABLED;
    }

    protected function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }
}
