<?php

namespace Sports\Sport\ScoreConfig;

use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Score\AgainstHelper as ScoreHomeAway;
use Sports\Sport;
use Sports\Game\Against as AgainstGame;
use Sports\Sport\Custom as SportCustom;
use Sports\Round\Number as RoundNumber;
use Sports\Competition\Sport as CompetitionSport;

class Service
{
    public function createDefault(CompetitionSport $competitionSport, RoundNumber $roundNumber)
    {
        $sportScoreConfig = new SportScoreConfig($competitionSport, $roundNumber);
        $sportScoreConfig->setDirection(SportScoreConfig::UPWARDS);
        $sportScoreConfig->setMaximum(0);
        $sportScoreConfig->setEnabled(true);
        $sport = $competitionSport->getSport();
        if ($sport->getCustomId() !== null && $this->hasNext($sport->getCustomId())) {
            $subScoreConfig = new SportScoreConfig($competitionSport, $roundNumber, $sportScoreConfig);
            $subScoreConfig->setDirection(SportScoreConfig::UPWARDS);
            $subScoreConfig->setMaximum(0);
            $subScoreConfig->setEnabled(false);
        }
        return $sportScoreConfig;
    }

    protected function hasNext(int $customId): bool
    {
        if (
            $customId === SportCustom::Badminton
            || $customId === SportCustom::Darts
            || $customId === SportCustom::Squash
            || $customId === SportCustom::TableTennis
            || $customId === SportCustom::Tennis
            || $customId === SportCustom::Volleyball
        ) {
            return true;
        }
        return false;
    }

    public function copy(CompetitionSport $competitionSport, RoundNumber $roundNumber, SportScoreConfig $sourceConfig)
    {
        $newScoreConfig = new SportScoreConfig($competitionSport, $roundNumber, null);
        $newScoreConfig->setDirection($sourceConfig->getDirection());
        $newScoreConfig->setMaximum($sourceConfig->getMaximum());
        $newScoreConfig->setEnabled($sourceConfig->getEnabled());
        $previousSubScoreConfig = $sourceConfig->getNext();
        if ($previousSubScoreConfig !== null) {
            $newSubScoreConfig = new SportScoreConfig($competitionSport, $roundNumber, $newScoreConfig);
            $newSubScoreConfig->setDirection($previousSubScoreConfig->getDirection());
            $newSubScoreConfig->setMaximum($previousSubScoreConfig->getMaximum());
            $newSubScoreConfig->setEnabled($previousSubScoreConfig->getEnabled());
        }
    }

    public function isDefault(SportScoreConfig $sportScoreConfig): bool
    {
        if ($sportScoreConfig->getDirection() !== SportScoreConfig::UPWARDS
            || $sportScoreConfig->getMaximum() !== 0
        ) {
            return false;
        }
        if ($sportScoreConfig->getNext() === null) {
            return true;
        }
        return $this->isDefault($sportScoreConfig->getNext());
    }

    public function areEqual(SportScoreConfig $sportScoreConfigA, SportScoreConfig $sportScoreConfigB): bool
    {
        if ($sportScoreConfigA->getDirection() !== $sportScoreConfigB->getDirection()
            || $sportScoreConfigA->getMaximum() !== $sportScoreConfigB->getMaximum()
        ) {
            return false;
        }
        if ($sportScoreConfigA->getNext() !== null && $sportScoreConfigB->getNext() !== null) {
            return $this->areEqual($sportScoreConfigA->getNext(), $sportScoreConfigB->getNext());
        }
        return $sportScoreConfigA->getNext() === $sportScoreConfigB->getNext();
    }

    public function getFinalScore(AgainstGame $game): ?ScoreHomeAway
    {
        if ($game->getScores()->count() === 0) {
            return null;
        }
        if ($game->getSportScoreConfig()->useSubScore()) {
            $home = 0;
            $away = 0;
            foreach ($game->getScores() as $score) {
                if ($score->getHome() > $score->getAway()) {
                    $home++;
                } elseif ($score->getHome() < $score->getAway()) {
                    $away++;
                }
            }
            return new ScoreHomeAway($home, $away);
        }
        $home = $game->getScores()->first()->getHome();
        $away = $game->getScores()->first()->getAway();
        return new ScoreHomeAway($home, $away);
    }

    public function getFinalSubScore(AgainstGame $game): ScoreHomeAway
    {
        $home = 0;
        $away = 0;
        foreach ($game->getScores() as $score) {
            $home += $score->getHome();
            $away += $score->getAway();
        }
        return new ScoreHomeAway($home, $away);
    }
}
