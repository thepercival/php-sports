<?php

declare(strict_types=1);

namespace Sports\Competition;

use DateTimeImmutable;
use Exception;
use League\Period\Period;
use Sports\Competition;
use Sports\League;
use Sports\Ranking\AgainstRuleSet;
use Sports\Season;
use Sports\State;

class Service
{
    public function __construct()
    {
    }

    /**
     * @param League $league
     * @param Season $season
     * @param AgainstRuleSet $ruleSet
     * @param DateTimeImmutable $startDateTime
     * @return Competition
     * @throws Exception
     */
    public function create(League $league, Season $season, AgainstRuleSet $ruleSet, DateTimeImmutable $startDateTime): Competition
    {
        if (!$season->getPeriod()->contains($startDateTime)) {
            throw new Exception("de startdatum van de competitie valt buiten het seizoen", E_ERROR);
        }

        $competition = new Competition($league, $season);
        $competition->setAgainstRuleSet($ruleSet);
        $competition->setStartDateTime($startDateTime);

        return $competition;
    }

    /**
     * @param Competition $competition
     * @param DateTimeImmutable $startDateTime
     * @return Period|null
     * @throws Exception
     */
    public function changeStartDateTime(Competition $competition, DateTimeImmutable $newStartDateTime): Period|null
    {
        if (!$competition->getSeason()->getPeriod()->contains($newStartDateTime)) {
            throw new Exception("de startdatum van de competitie valt buiten het seizoen", E_ERROR);
        }

        if ($newStartDateTime->getTimestamp() > $competition->getStartDateTime()->getTimestamp()) {
            $period = new Period($competition->getStartDateTime(), $newStartDateTime);
        } else {
            $period = null;
        }

        $competition->setStartDateTime($newStartDateTime);

        return $period;
    }

    /**
     * @param Competition $competition
     * @param AgainstRuleSet $ruleSet
     * @return Competition
     * @throws Exception
     */
    public function changeAgainstRuleSet(Competition $competition, AgainstRuleSet $ruleSet)
    {
        $competition->setAgainstRuleSet($ruleSet);

        return $competition;
    }
}
