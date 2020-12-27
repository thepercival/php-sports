<?php

declare(strict_types=1);

namespace Sports;

use DateTimeImmutable;
use \Doctrine\Common\Collections\ArrayCollection;
use \Doctrine\ORM\PersistentCollection;
use Sports\Ranking\Service\Against as AgainstRankingService;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Competition\Field as CompetitionField;
use SportsHelpers\Identifiable;
use Sports\Competitor\Team as TeamCompetitor;
use Sports\Competition\Referee;

class Competition extends Identifiable
{
    /**
     * @var League
     */
    private $league;

    /**
     * @var Season
     */
    private $season;

    /**
     * @var DateTimeImmutable
     */
    private $startDateTime;

    /**
     * @var int
     */
    private $ruleSet;

    /**
     * @var int
     */
    private $state;

    /**
     * @var ArrayCollection
     */
    private $roundNumbers;

    /**
     * @var ArrayCollection|Referee[]
     */
    private $referees;
    /**
     * @var ArrayCollection|CompetitionSport[]
     */
    private $sports;
    /**
     * @var ArrayCollection|TeamCompetitor[]
     */
    private $teamCompetitors;

    const MIN_COMPETITORS = 3;
    const MAX_COMPETITORS = 40;

    public function __construct(League $league, Season $season)
    {
        $this->setLeague($league);
        $this->season = $season;
        $this->ruleSet = AgainstRankingService::RULESSET_WC;
        $this->state = State::Created;
        $this->roundNumbers = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->teamCompetitors = new ArrayCollection();
    }

    /**
     * @return League
     */
    public function getLeague()
    {
        return $this->league;
    }

    protected function setLeague(League $league)
    {
        $competitions = $league->getCompetitions();
        if (!$competitions->contains($this)) {
            $competitions->add($this) ;
        }
        $this->league = $league;
    }

    /**
     * @return Season
     */
    public function getSeason()
    {
        return $this->season;
    }

    public function getName(): string
    {
        return $this->getLeague()->getName() . ' ' . $this->getSeason()->getName();
    }

    /**
     * @return DateTimeImmutable
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * @param DateTimeImmutable $datetime
     */
    public function setStartDateTime(DateTimeImmutable $datetime)
    {
        $this->startDateTime = $datetime;
    }

    /**
     * @return int
     */
    public function getRuleSet()
    {
        return $this->ruleSet;
    }

    /**
     * @param int $ruleSet
     */
    public function setRuleSet($ruleSet)
    {
        $this->ruleSet = $ruleSet;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return ArrayCollection
     */
    public function getRoundNumbers()
    {
        return $this->roundNumbers;
    }

    /**
     * @return ArrayCollection | Referee[]
     */
    public function getReferees()
    {
        return $this->referees;
    }

    /**
     * @param ArrayCollection | Referee[] $referees
     */
    public function setReferees($referees)
    {
        $this->referees = $referees;
    }

    /**
     * @return Referee
     */
    public function getReferee(int $priority)
    {
        $referees = array_filter(
            $this->getReferees()->toArray(),
            function (Referee $referee) use ($priority): bool {
                return $referee->getPriority() === $priority;
            }
        );
        return array_shift($referees);
    }

    /**
     * @return ArrayCollection | TeamCompetitor[]
     */
    public function getTeamCompetitors()
    {
        return $this->teamCompetitors;
    }

    /**
     * @param ArrayCollection | TeamCompetitor[] $teamCompetitors
     */
    public function setTeamCompetitors($teamCompetitors)
    {
        $this->teamCompetitors = $teamCompetitors;
    }

    public function getField(int $priority): ?CompetitionField
    {
        foreach ($this->getSports() as $competitionSport) {
            $field = $competitionSport->getField($priority);
            if ($field !== null) {
                return $field;
            }
        }
        return null;
    }

    /**
     * @return ArrayCollection | PersistentCollection | CompetitionSport[]
     */
    public function getSports()
    {
        return $this->sports;
    }

    public function getSport(Sport $sport): ?CompetitionSport
    {
        $foundConfigs = $this->sports->filter(function (CompetitionSport $competitionSport) use ($sport): bool {
            return $competitionSport->getSport() === $sport;
        });
        $foundConfig = $foundConfigs->first();
        return $foundConfig ? $foundConfig : null;
    }

    public function hasMultipleSports(): bool
    {
        return $this->sports->count() > 1;
    }

//    /**
//     * @param int|string $sportId
//     * @return Sport|null
//     */
//    public function getSportBySportId($sportId): ?Sport
//    {
//        foreach ($this->getSportConfigs() as $sportConfig) {
//            if ($sportConfig->getSport()->getId() === $sportId) {
//                return $sportConfig->getSport();
//            }
//        }
//        return null;
//    }

    /**
     * @return array | CompetitionField[]
     */
    public function getFields(): array
    {
        $fields = [];
        foreach( $this->getSports() as $competitionSport ) {
            $fields = array_merge($fields, $competitionSport->getFields()->toArray() );
        }
        return $fields;
    }
}
