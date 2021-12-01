<?php
declare(strict_types=1);

namespace Sports\Qualify\AgainstConfig;

use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Ranking\PointsCalculation;
use Sports\Sport;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Sport\Custom as SportCustom;
use Sports\Round;
use SportsHelpers\GameMode;

class Service
{
    public function createDefault(CompetitionSport $competitionSport, Round $round): QualifyConfig
    {
        $sport = $competitionSport->getSport();
        $qualifyConfig = new QualifyConfig($competitionSport, $round, $this->getDefaultPointCalculation($competitionSport));
        $qualifyConfig->setWinPoints($this->getDefaultWinPoints($sport));
        $qualifyConfig->setDrawPoints($this->getDefaultDrawPoints($sport));
        $qualifyConfig->setWinPointsExt($this->getDefaultWinPointsExt($sport));
        $qualifyConfig->setDrawPointsExt($this->getDefaultDrawPointsExt($sport));
        $qualifyConfig->setLosePointsExt($this->getDefaultLosePointsExt($sport));
        return $qualifyConfig;
    }

    protected function getDefaultPointCalculation(CompetitionSport $competitionSport): PointsCalculation
    {
        if ($competitionSport->getGameMode() === GameMode::Against) {
            return PointsCalculation::AgainstGamePoints;
        }
        return PointsCalculation::Scores;
    }

    protected function getDefaultWinPoints(Sport $sport): float
    {
        return $sport->getCustomId() !== SportCustom::Chess ? 3 : 1;
    }

    protected function getDefaultDrawPoints(Sport $sport): float
    {
        return $sport->getCustomId() !== SportCustom::Chess ? 1 : 0.5;
    }

    protected function getDefaultWinPointsExt(Sport $sport): float
    {
        return $sport->getCustomId() !== SportCustom::Chess ? 2 : 1;
    }

    protected function getDefaultDrawPointsExt(Sport $sport): float
    {
        return $sport->getCustomId() !== SportCustom::Chess ? 1 : 0.5;
    }

    protected function getDefaultLosePointsExt(Sport $sport): float
    {
        return $sport->getCustomId() === SportCustom::IceHockey ? 1 : 0;
    }

//
//    public function isDefault(SportConfig $sportConfig): bool
//    {
//        $sport = $sportConfig->getSport();
//        return ($sportConfig->getWinPoints() !== $this->getDefaultWinPoints($sport)
//            || $sportConfig->getDrawPoints() !== $this->getDefaultDrawPoints($sport)
//            || $sportConfig->getWinPointsExt() !== $this->getDefaultWinPointsExt($sport)
//            || $sportConfig->getDrawPointsExt() !== $this->getDefaultDrawPointsExt($sport)
//            || $sportConfig->getLosePointsExt() !== $this->getDefaultLosePointsExt($sport)
//            || $sportConfig->getPointsCalculation() !== SportConfig::POINTS_CALC_GAMEPOINTS
//            || $sportConfig->getNrOfGamePlaces() !== SportConfig::DEFAULT_NROFGAMEPLACES
//        );
//    }
//
//    public function areEqual(SportConfig $sportConfigA, SportConfig $sportConfigB): bool
//    {
//        return ($sportConfigA->getSport() !== $sportConfigB->getSport()
//            || $sportConfigA->getWinPoints() !== $sportConfigB->getWinPoints()
//            || $sportConfigA->getDrawPoints() !== $sportConfigB->getDrawPoints()
//            || $sportConfigA->getWinPointsExt() !== $sportConfigB->getWinPointsExt()
//            || $sportConfigA->getDrawPointsExt() !== $sportConfigB->getDrawPointsExt()
//            || $sportConfigA->getLosePointsExt() !== $sportConfigB->getLosePointsExt()
//            || $sportConfigA->getPointsCalculation() !== $sportConfigB->getPointsCalculation()
//            || $sportConfigA->getNrOfGamePlaces() !== $sportConfigB->getNrOfGamePlaces()
//        );
//    }
//
//    public function copy(SportConfig $sourceConfig, Competition $newCompetition, Sport $sport): SportConfig
//    {
//        $newConfig = new SportConfig($sport, $newCompetition);
//        $newConfig->setWinPoints($sourceConfig->getWinPoints());
//        $newConfig->setDrawPoints($sourceConfig->getDrawPoints());
//        $newConfig->setWinPointsExt($sourceConfig->getWinPointsExt());
//        $newConfig->setDrawPointsExt($sourceConfig->getDrawPointsExt());
//        $newConfig->setLosePointsExt($sourceConfig->getLosePointsExt());
//        $newConfig->setPointsCalculation($sourceConfig->getPointsCalculation());
//        $newConfig->setNrOfGamePlaces($sourceConfig->getNrOfGamePlaces());
//        $newConfig->setVersusMode($sourceConfig->getVersusMode());
//        return $newConfig;
//    }
//

    public function copy(CompetitionSport $competitionSport, Round $round, QualifyConfig $sourceConfig): void
    {
        $newConfig = new QualifyConfig($competitionSport, $round, $sourceConfig->getPointsCalculation());
        $newConfig->setWinPoints($sourceConfig->getWinPoints());
        $newConfig->setDrawPoints($sourceConfig->getDrawPoints());
        $newConfig->setWinPointsExt($sourceConfig->getWinPointsExt());
        $newConfig->setDrawPointsExt($sourceConfig->getDrawPointsExt());
        $newConfig->setLosePointsExt($sourceConfig->getLosePointsExt());
    }
}
