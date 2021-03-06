<?php
declare(strict_types=1);

namespace Sports\Planning;

use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;

class Config extends Identifiable
{
    protected bool $teamupDep = false;
    protected int $nrOfHeadtoheadDep = 1;

    const DEFAULTEXTENSION = false;
    const DEFAULTENABLETIME = true;
    const DEFAULTGAMEAMOUNT = 1;

    public function __construct(
        protected RoundNumber $roundNumber,
        protected int $editMode,
        protected int $gamePlaceStrategy,
        protected bool $extension,
        protected bool $enableTime,
        protected int $minutesPerGame,
        protected int $minutesPerGameExt,
        protected int $minutesBetweenGames,
        protected int $minutesAfter,
        protected int $selfReferee
    ) {
        $this->roundNumber->setPlanningConfig($this);
    }

    public function getEditMode(): int
    {
        return $this->editMode;
    }

    public function setEditMode(int $editMode): void
    {
        $this->editMode = $editMode;
    }

    public function getGamePlaceStrategy(): int
    {
        return $this->gamePlaceStrategy;
    }

    public function setGamePlaceStrategy(int $gamePlaceStrategy): void
    {
        $this->gamePlaceStrategy = $gamePlaceStrategy;
    }

    public function getExtension(): bool
    {
        return $this->extension;
    }

    public function setExtension(bool $extension): void
    {
        $this->extension = $extension;
    }

    public function getEnableTime(): bool
    {
        return $this->enableTime;
    }

    public function setEnableTime(bool $enableTime): void
    {
        $this->enableTime = $enableTime;
    }

    public function getMinutesBetweenGames(): int
    {
        return $this->minutesBetweenGames;
    }

    public function setMinutesBetweenGames(int $minutesBetweenGames): void
    {
        $this->minutesBetweenGames = $minutesBetweenGames;
    }

    public function getMinutesAfter(): int
    {
        return $this->minutesAfter;
    }

    public function setMinutesAfter(int $minutesAfter): void
    {
        $this->minutesAfter = $minutesAfter;
    }

    public function getMaxNrOfMinutesPerGame(): int
    {
        $maxNrOfMinutes = $this->getMinutesPerGame();
        if ($this->getExtension()) {
            $maxNrOfMinutes += $this->getMinutesPerGameExt();
        }
        return $maxNrOfMinutes;
    }

    public function getMinutesPerGame(): int
    {
        return $this->minutesPerGame;
    }

    public function setMinutesPerGame(int $minutesPerGame): void
    {
        $this->minutesPerGame = $minutesPerGame;
    }

    public function getMinutesPerGameExt(): int
    {
        return $this->minutesPerGameExt;
    }

    public function setMinutesPerGameExt(int $minutesPerGameExt): void
    {
        $this->minutesPerGameExt = $minutesPerGameExt;
    }

    public function getSelfReferee(): int
    {
        return $this->selfReferee;
    }

    public function setSelfReferee(int $selfReferee): void
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
