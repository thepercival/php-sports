<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 18-6-19
 * Time: 15:32
 */

namespace Sports\Planning;

use Sports\Round\Number as RoundNumber;
use SportsPlanning\Input as PlanningInput;

class Config
{
    /**
     * @var RoundNumber
     */
    protected $roundNumber;
    /**
     * @var int
     */
    protected $id;
    /**
     * @var bool
     */
    protected $extension;
    /**
     * @var bool
     */
    protected $enableTime;
    /**
     * @var int
     */
    protected $minutesPerGame;
    /**
     * @var int
     */
    protected $minutesPerGameExt;
    /**
     * @var int
     */
    protected $minutesBetweenGames;
    /**
     * @var int
     */
    protected $minutesAfter;
    /**
     * @var bool
     */
    protected $teamup;
    /**
     * @var int
     */
    protected $selfReferee;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;

    const DEFAULTEXTENSION = false;
    const DEFAULTENABLETIME = true;
    const DEFAULTNROFHEADTOHEAD = 1;

    public function __construct(RoundNumber $roundNumber)
    {
        $this->roundNumber = $roundNumber;
        $this->roundNumber->setPlanningConfig($this);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id = null)
    {
        $this->id = $id;
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

    /**
     * @return int
     */
    public function getMinutesBetweenGames()
    {
        return $this->minutesBetweenGames;
    }

    /**
     * @param int $minutesBetweenGames
     */
    public function setMinutesBetweenGames(int $minutesBetweenGames)
    {
        $this->minutesBetweenGames = $minutesBetweenGames;
    }

    /**
     * @return int
     */
    public function getMinutesAfter()
    {
        return $this->minutesAfter;
    }

    /**
     * @param int $minutesAfter
     */
    public function setMinutesAfter(int $minutesAfter)
    {
        $this->minutesAfter = $minutesAfter;
    }

    public function getMaxNrOfMinutesPerGame(): int
    {
        return $this->getMinutesPerGame() + $this->getMinutesPerGameExt();
    }

    /**
     * @return int
     */
    public function getMinutesPerGame()
    {
        return $this->minutesPerGame;
    }

    /**
     * @param int $minutesPerGame
     */
    public function setMinutesPerGame(int $minutesPerGame)
    {
        $this->minutesPerGame = $minutesPerGame;
    }

    /**
     * @return int
     */
    public function getMinutesPerGameExt()
    {
        return $this->minutesPerGameExt;
    }

    /**
     * @param int $minutesPerGameExt
     */
    public function setMinutesPerGameExt(int $minutesPerGameExt)
    {
        $this->minutesPerGameExt = $minutesPerGameExt;
    }

    public function getTeamup(): bool
    {
        return $this->teamup;
    }

    public function setTeamup(bool $teamup)
    {
        $this->teamup = $teamup;
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
        return $this->selfReferee !== PlanningInput::SELFREFEREE_DISABLED;
    }

    public function getNrOfHeadtohead(): int
    {
        return $this->nrOfHeadtohead;
    }

    public function setNrOfHeadtohead(int $nrOfHeadtohead)
    {
        $this->nrOfHeadtohead = $nrOfHeadtohead;
    }

    public function getNrOfCompetitorsPerGame(): int
    {
        return $this->getTeamup() ? 4 : 2;
    }

    /**
     * @return RoundNumber
     */
    protected function getRoundNumber(): RoundNumber
    {
        return $this->roundNumber;
    }
}
