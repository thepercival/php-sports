<?php

declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Ranking\PointsCalculation;
use Sports\Sport;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport\Custom as CustomSport;
use Sports\Round;
use SportsHelpers\GameMode;

final class Service
{
    public function createDefault(CompetitionSport $competitionSport, Round $round): QualifyConfig
    {
        $sport = $competitionSport->getSport();
        return new QualifyConfig(
            $competitionSport,
            $round,
            $competitionSport->getDefaultPointsCalculation(),
            $sport->getDefaultWinPoints(),
            $sport->getDefaultDrawPoints(),
            $sport->getDefaultWinPointsExt(),
            $sport->getDefaultDrawPointsExt(),
            $sport->getDefaultLosePointsExt()
        );
    }

    public function copy(QualifyConfig $fromConfig, CompetitionSport $competitionSport, Round $round): void
    {
        new QualifyConfig(
            $competitionSport,
            $round,
            $fromConfig->getPointsCalculation(),
            $fromConfig->getWinPoints(),
            $fromConfig->getDrawPoints(),
            $fromConfig->getWinPointsExt(),
            $fromConfig->getDrawPointsExt(),
            $fromConfig->getLosePointsExt()
        );
    }
}
