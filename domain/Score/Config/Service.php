<?php

declare(strict_types=1);

namespace Sports\Score\Config;

use Sports\Score\Config as ScoreConfig;
use Sports\Score\AgainstHelper as AgainstScore;
use Sports\Score\Together as TogetherScore;
use Sports\Game\Place\Together as TogetherGamePlace;
use Sports\Game\Against as AgainstGame;
use Sports\Sport\Custom as SportCustom;
use Sports\Round;
use Sports\Competition\Sport as CompetitionSport;

class Service
{
    public function createDefault(CompetitionSport $competitionSport, Round $round)
    {
        $scoreConfig = new ScoreConfig($competitionSport, $round);
        $scoreConfig->setDirection(ScoreConfig::UPWARDS);
        $scoreConfig->setMaximum(0);
        $scoreConfig->setEnabled(true);
        $sport = $competitionSport->getSport();
        if ($sport->getCustomId() !== null && $this->hasNext($sport->getCustomId())) {
            $subScoreConfig = new ScoreConfig($competitionSport, $round, $scoreConfig);
            $subScoreConfig->setDirection(ScoreConfig::UPWARDS);
            $subScoreConfig->setMaximum(0);
            $subScoreConfig->setEnabled(false);
        }
        return $scoreConfig;
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
            || $customId === SportCustom::BadmintonDouble
            || $customId === SportCustom::SquashDouble
            || $customId === SportCustom::TennisDouble
            || $customId === SportCustom::TableTennisDouble
        ) {
            return true;
        }
        return false;
    }

    public function copy(CompetitionSport $competitionSport, Round $round, ScoreConfig $sourceConfig)
    {
        $newScoreConfig = new ScoreConfig($competitionSport, $round, null);
        $newScoreConfig->setDirection($sourceConfig->getDirection());
        $newScoreConfig->setMaximum($sourceConfig->getMaximum());
        $newScoreConfig->setEnabled($sourceConfig->getEnabled());
        $previousSubScoreConfig = $sourceConfig->getNext();
        if ($previousSubScoreConfig !== null) {
            $newSubScoreConfig = new ScoreConfig($competitionSport, $round, $newScoreConfig);
            $newSubScoreConfig->setDirection($previousSubScoreConfig->getDirection());
            $newSubScoreConfig->setMaximum($previousSubScoreConfig->getMaximum());
            $newSubScoreConfig->setEnabled($previousSubScoreConfig->getEnabled());
        }
    }

    public function isDefault(ScoreConfig $scoreConfig): bool
    {
        if ($scoreConfig->getDirection() !== ScoreConfig::UPWARDS
            || $scoreConfig->getMaximum() !== 0
        ) {
            return false;
        }
        if ($scoreConfig->getNext() === null) {
            return true;
        }
        return $this->isDefault($scoreConfig->getNext());
    }

    public function areEqual(ScoreConfig $scoreConfigA, ScoreConfig $scoreConfigB): bool
    {
        if ($scoreConfigA->getDirection() !== $scoreConfigB->getDirection()
            || $scoreConfigA->getMaximum() !== $scoreConfigB->getMaximum()
        ) {
            return false;
        }
        if ($scoreConfigA->getNext() !== null && $scoreConfigB->getNext() !== null) {
            return $this->areEqual($scoreConfigA->getNext(), $scoreConfigB->getNext());
        }
        return $scoreConfigA->getNext() === $scoreConfigB->getNext();
    }

    public function getFinalAgainstScore(AgainstGame $game): ?AgainstScore
    {
        if ($game->getScores()->count() === 0) {
            return null;
        }
        if ($game->getScoreConfig()->useSubScore()) {
            $home = 0;
            $away = 0;
            foreach ($game->getScores() as $score) {
                if ($score->getHome() > $score->getAway()) {
                    $home++;
                } elseif ($score->getHome() < $score->getAway()) {
                    $away++;
                }
            }
            return new AgainstScore($home, $away);
        }
        $home = $game->getScores()->first()->getHome();
        $away = $game->getScores()->first()->getAway();
        return new AgainstScore($home, $away);
    }

    public function getFinalAgainstSubScore(AgainstGame $game): AgainstScore
    {
        $home = 0;
        $away = 0;
        foreach ($game->getScores() as $score) {
            $home += $score->getHome();
            $away += $score->getAway();
        }
        return new AgainstScore($home, $away);
    }

    public function getFinalTogetherScore(TogetherGamePlace $gamePlace): int
    {
        $score = 0;
        if ($gamePlace->getScores()->count() === 0) {
            return $score;
        }
        if ($gamePlace->getGame()->getScoreConfig()->useSubScore()) {
            $score = 0;
            foreach ($gamePlace->getScores() as $subScore) {
                $score += $subScore;
            }
            return $score;
        }
        return $gamePlace->getScores()->first()->getScore();
    }

    public function getFinalTogetherSubScore(TogetherGamePlace $gamePlace): int
    {
        $score = 0;
        /** @var TogetherScore $togetherScore */
        foreach ($gamePlace->getScores() as $togetherScore) {
            $score += $togetherScore->getScore();
        }
        return $score;
    }
}
