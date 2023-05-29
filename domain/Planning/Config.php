<?php

declare(strict_types=1);

namespace Sports\Planning;

use Sports\Round\Number as RoundNumber;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;

class Config extends Identifiable
{
    public const DEFAULTEXTENSION = false;
    public const DEFAULTENABLETIME = true;
    public const DEFAULTGAMEAMOUNT = 1;

    public function __construct(
        protected RoundNumber $roundNumber,
        protected EditMode $editMode,
        protected bool $extension,
        protected bool $enableTime,
        protected int $minutesPerGame,
        protected int $minutesPerGameExt,
        protected int $minutesBetweenGames,
        protected int $minutesAfter,
        protected bool $perPoule,
        protected SelfReferee $selfReferee
    ) {
        $this->roundNumber->setPlanningConfig($this);
    }

    public function getEditMode(): EditMode
    {
        return $this->editMode;
    }

    public function setEditMode(EditMode $editMode): void
    {
        $this->editMode = $editMode;
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

    public function getPerPoule(): bool
    {
        return $this->perPoule;
    }

//    public function setMinutesPerGameExt(int $minutesPerGameExt): void
//    {
//        $this->minutesPerGameExt = $minutesPerGameExt;
//    }

    public function getSelfReferee(): SelfReferee
    {
        return $this->selfReferee;
    }

    public function setSelfReferee(SelfReferee $selfReferee): void
    {
        $this->selfReferee = $selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== SelfReferee::Disabled;
    }

    protected function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }
}
