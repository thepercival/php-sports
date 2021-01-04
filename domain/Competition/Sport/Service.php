<?php
declare(strict_types=1);

namespace Sports\Competition\Sport;

use Sports\Sport;
use Sports\Sport\ScoreConfig as SportScoreConfig;
use Sports\Sport\GameAmountConfig as SportGameAmountConfig;
use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Sport\ScoreConfig\Service as ScoreConfigService;
use Sports\Sport\GameAmountConfig\Service as GameAmountConfigService;
use Sports\Qualify\AgainstConfig\Service as QualifyConfigService;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Structure;

class Service
{
    protected ScoreConfigService $scoreConfigService;
    protected GameAmountConfigService $gameAmountConfigService;
    protected QualifyConfigService $qualifyConfigService;

    public function __construct()
    {
        $this->scoreConfigService = new ScoreConfigService();
        $this->gameAmountConfigService = new GameAmountConfigService();
        $this->qualifyConfigService = new QualifyConfigService();
    }

    public function createDefault(Sport $sport, Competition $competition, Structure $structure = null): CompetitionSport
    {
        $competitionSport = new CompetitionSport($sport, $competition);
        if ($structure !== null) {
            $this->addToStructure($competitionSport, $structure);
        }
        return $competitionSport;
    }

    public function copy(Competition $newCompetition, Sport $sport): CompetitionSport
    {
        return new CompetitionSport($sport, $newCompetition);
    }

    public function addToStructure(CompetitionSport $competitionSport, Structure $structure)
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if ($roundNumber->hasPrevious() === false || $roundNumber->getSportScoreConfigs()->count() > 0) {
                $this->scoreConfigService->createDefault($competitionSport, $roundNumber);
                $this->gameAmountConfigService->createDefault($competitionSport, $roundNumber);
                $this->qualifyConfigService->createDefault($competitionSport, $roundNumber);
            }
            $roundNumber = $roundNumber->getNext();
        }
    }

    public function remove(CompetitionSport $competitionSport, Structure $structure)
    {
        $competitionSport->getFields()->clear();
        $competitionSport->getCompetition()->getSports()->removeElement($competitionSport);

        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber) {
            $scoreConfigs = $roundNumber->getSportScoreConfigs();
            $scoreConfigs->filter(
                function (SportScoreConfig $scoreConfigIt) use ($competitionSport): bool {
                    return $scoreConfigIt->getCompetitionSport() === $competitionSport;
                }
            )->forAll(
                function (SportScoreConfig $scoreConfigIt) use ($scoreConfigs): bool {
                    return $scoreConfigs->removeElement($scoreConfigIt);
                }
            );

            $gameAmountConfigs = $roundNumber->getSportGameAmountConfigs();
            $gameAmountConfigs->filter(
                function (SportGameAmountConfig $gameAmountConfigIt) use ($competitionSport): bool {
                    return $gameAmountConfigIt->getCompetitionSport() === $competitionSport;
                }
            )->forAll(
                function (SportGameAmountConfig $gameAmountConfigIt) use ($gameAmountConfigs): bool {
                    return $gameAmountConfigs->removeElement($gameAmountConfigIt);
                }
            );
            
            $qualifyConfigs = $roundNumber->getQualifyAgainstConfigs();
            $qualifyConfigs->filter(
                function (QualifyConfig $qualifyConfigIt) use ($competitionSport): bool {
                    return $qualifyConfigIt->getCompetitionSport() === $competitionSport;
                }
            )->forAll(
                function (QualifyConfig $qualifyConfigIt) use ($qualifyConfigs): bool {
                    return $qualifyConfigs->removeElement($qualifyConfigIt);
                }
            );
            $roundNumber = $roundNumber->getNext();
        }
    }
}