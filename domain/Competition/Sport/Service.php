<?php
declare(strict_types=1);

namespace Sports\Competition\Sport;

use Sports\Game\CreationStrategy;
use Sports\Round;
use Sports\Sport;
use Sports\Score\Config as ScoreConfig;
use Sports\Planning\GameAmountConfig as GameAmountConfig;
use Sports\Qualify\AgainstConfig as QualifyConfig;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Planning\GameAmountConfig\Service as GameAmountConfigService;
use Sports\Qualify\AgainstConfig\Service as QualifyConfigService;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Structure;
use SportsHelpers\GameMode;

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
            if ($roundNumber->hasPrevious() === false || $roundNumber->getGameAmountConfigs()->count() > 0) {
                $this->gameAmountConfigService->createDefault($competitionSport, $roundNumber);
            }
            $roundNumber = $roundNumber->getNext();
        }

        /**
         * @param array|Round[] $rounds
         */
        $addToRounds = function (array $rounds) use ($competitionSport, &$addToRounds): void {
            foreach ($rounds as $round) {
                if ($round->isRoot() || $round->getScoreConfigs()->count() > 0) {
                    $this->scoreConfigService->createDefault($competitionSport, $round);
                }
                if ($round->isRoot() || $round->getQualifyAgainstConfigs()->count() > 0) {
                    $this->qualifyConfigService->createDefault($competitionSport, $round);
                }
                $addToRounds($round->getChildren());
            }
        };
        $addToRounds([$structure->getRootRound()]);
    }

    public function remove(CompetitionSport $competitionSport, Structure $structure)
    {
        $competitionSport->getFields()->clear();
        $competitionSport->getCompetition()->getSports()->removeElement($competitionSport);

        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber) {
            $gameAmountConfigs = $roundNumber->getGameAmountConfigs();
            $gameAmountConfigs->filter(
                function (GameAmountConfig $gameAmountConfigIt) use ($competitionSport): bool {
                    return $gameAmountConfigIt->getCompetitionSport() === $competitionSport;
                }
            )->forAll(
                function (GameAmountConfig $gameAmountConfigIt) use ($gameAmountConfigs): bool {
                    return $gameAmountConfigs->removeElement($gameAmountConfigIt);
                }
            );
            $roundNumber = $roundNumber->getNext();
        }

        /**
         * @param array|Round[] $rounds
         */
        $removeFromRounds = function (array $rounds) use ($competitionSport, &$removeFromRounds): void {
            foreach ($rounds as $round) {
                $scoreConfigs = $round->getScoreConfigs();
                $scoreConfigs->filter(
                    function (ScoreConfig $scoreConfigIt) use ($competitionSport): bool {
                        return $scoreConfigIt->getCompetitionSport() === $competitionSport;
                    }
                )->forAll(
                    function (ScoreConfig $scoreConfigIt) use ($scoreConfigs): bool {
                        return $scoreConfigs->removeElement($scoreConfigIt);
                    }
                );

                $qualifyConfigs = $round->getQualifyAgainstConfigs();
                $qualifyConfigs->filter(
                    function (QualifyConfig $qualifyConfigIt) use ($competitionSport): bool {
                        return $qualifyConfigIt->getCompetitionSport() === $competitionSport;
                    }
                )->forAll(
                    function (QualifyConfig $qualifyConfigIt) use ($qualifyConfigs): bool {
                        return $qualifyConfigs->removeElement($qualifyConfigIt);
                    }
                );
                $removeFromRounds($round->getChildren());
            }
        };
        $removeFromRounds([$structure->getRootRound()]);
    }
}
