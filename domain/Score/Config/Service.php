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
    public function createDefault(CompetitionSport $competitionSport, Round $round): ScoreConfig
    {
        $scoreConfig = new ScoreConfig(
            $competitionSport,
            $round,
            ScoreConfig::UPWARDS,
            0,
            true
        );
        $sport = $competitionSport->getSport();
        if ($this->hasNext($sport->getCustomId())) {
            new ScoreConfig(
                $competitionSport,
                $round,
                ScoreConfig::UPWARDS,
                0,
                false,
                $scoreConfig
            );
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

    public function copy(CompetitionSport $competitionSport, Round $round, ScoreConfig $sourceConfig): void
    {
        $newScoreConfig = new ScoreConfig(
            $competitionSport,
            $round,
            $sourceConfig->getDirection(),
            $sourceConfig->getMaximum(),
            $sourceConfig->getEnabled(),
            null
        );

        $previousSubScoreConfig = $sourceConfig->getNext();
        if ($previousSubScoreConfig !== null) {
            new ScoreConfig(
                $competitionSport,
                $round,
                $previousSubScoreConfig->getDirection(),
                $previousSubScoreConfig->getMaximum(),
                $previousSubScoreConfig->getEnabled(),
                $newScoreConfig
            );
        }
    }

    public function isDefault(ScoreConfig $scoreConfig): bool
    {
        if ($scoreConfig->getDirection() !== ScoreConfig::UPWARDS
            || $scoreConfig->getMaximum() !== 0
        ) {
            return false;
        }
        $nextScoreConfig = $scoreConfig->getNext();
        if ($nextScoreConfig === null) {
            return true;
        }
        return $this->isDefault($nextScoreConfig);
    }

    public function areEqual(ScoreConfig $scoreConfigA, ScoreConfig $scoreConfigB): bool
    {
        if ($scoreConfigA->getDirection() !== $scoreConfigB->getDirection()
            || $scoreConfigA->getMaximum() !== $scoreConfigB->getMaximum()
        ) {
            return false;
        }
        $nextScoreConfigA = $scoreConfigA->getNext();
        $nextScoreConfigB = $scoreConfigB->getNext();
        if ($nextScoreConfigA !== null && $nextScoreConfigB !== null) {
            return $this->areEqual($nextScoreConfigA, $nextScoreConfigB);
        }
        return $nextScoreConfigA === $nextScoreConfigB;
    }

    public function getFinalAgainstScore(AgainstGame $game): ?AgainstScore
    {
        $firstScore = $game->getScores()->first();
        if ($firstScore === false) {
            return null;
        }
        if (!$game->getScoreConfig()->useSubScore()) {
            return new AgainstScore($firstScore->getHome(), $firstScore->getAway());
        }
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
        $firstScore = $gamePlace->getScores()->first();
        if ($firstScore === false) {
            return $score;
        }
        if (!$gamePlace->getGame()->getScoreConfig()->useSubScore()) {
            return $firstScore->getScore();
        }
        foreach ($gamePlace->getScores() as $subScore) {
            $score += $subScore->getScore();
        }
        return $score;
    }

    public function getFinalTogetherSubScore(TogetherGamePlace $gamePlace): int
    {
        $score = 0;
        foreach ($gamePlace->getScores() as $togetherScore) {
            $score += $togetherScore->getScore();
        }
        return $score;
    }
}
