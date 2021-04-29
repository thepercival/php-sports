<?php
declare(strict_types=1);

namespace Sports\Competition\Sport;

use Sports\Round;
use Closure;
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
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant as SportVariant;

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

    /*public function createDefault(Sport $sport, Competition $competition, Structure $structure = null): CompetitionSport
    {
        $competitionSport = new CompetitionSport(
            $sport,
            $competition,
            $this->getDefaultSportVariant($sport)->createPersistVariant()
        );
        if ($structure !== null) {
            $this->addToStructure($competitionSport, $structure);
        }
        return $competitionSport;
    }*/

    /*public function copy(Competition $newCompetition, Sport $sport): CompetitionSport
    {
        return new CompetitionSport($sport, $newCompetition);
    }*/

    public function addToStructure(CompetitionSport $competitionSport, Structure $structure): void
    {
        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber !== null) {
            if ($roundNumber->hasPrevious() === false || $roundNumber->getGameAmountConfigs()->count() > 0) {
                $this->gameAmountConfigService->createDefault($competitionSport, $roundNumber);
            }
            $roundNumber = $roundNumber->getNext();
        }

        $addToRounds = function (array $rounds) use ($competitionSport, &$addToRounds): void {
            /** @var list<Round> $rounds */
            foreach ($rounds as $round) {
                if ($round->isRoot() || $round->getScoreConfigs()->count() > 0) {
                    $this->scoreConfigService->createDefault($competitionSport, $round);
                }
                if ($round->isRoot() || $round->getQualifyAgainstConfigs()->count() > 0) {
                    $this->qualifyConfigService->createDefault($competitionSport, $round);
                }
                /** @var Closure(list<Round>):void $addToRounds */
                $addToRounds($round->getChildren());
            }
        };
        $addToRounds([$structure->getRootRound()]);
    }

    public function remove(CompetitionSport $competitionSport, Structure $structure): void
    {
        $competitionSport->getFields()->clear();
        $competitionSport->getCompetition()->getSports()->removeElement($competitionSport);

        $roundNumber = $structure->getFirstRoundNumber();
        while ($roundNumber) {
            $gameAmountConfigs = $roundNumber->getGameAmountConfigs()->filter(
                function (GameAmountConfig $gameAmountConfigIt) use ($competitionSport): bool {
                    return $gameAmountConfigIt->getCompetitionSport() === $competitionSport;
                }
            );
            while ($gameAmountConfig = $gameAmountConfigs->first()) {
                $gameAmountConfigs->removeElement($gameAmountConfig);
            }
            $roundNumber = $roundNumber->getNext();
        }

        $removeFromRounds = function (array $rounds) use ($competitionSport, &$removeFromRounds): void {
            /** @var list<Round> $rounds */
            foreach ($rounds as $round) {
                $scoreConfigs = $round->getScoreConfigs()->filter(
                    function (ScoreConfig $scoreConfigIt) use ($competitionSport): bool {
                        return $scoreConfigIt->getCompetitionSport() === $competitionSport;
                    }
                );
                while ($scoreConfig = $scoreConfigs->first()) {
                    $scoreConfigs->removeElement($scoreConfig);
                }

                $qualifyConfigs = $round->getQualifyAgainstConfigs()->filter(
                    function (QualifyConfig $qualifyConfigIt) use ($competitionSport): bool {
                        return $qualifyConfigIt->getCompetitionSport() === $competitionSport;
                    }
                );
                while ($qualifyConfig = $qualifyConfigs->first()) {
                    $qualifyConfigs->removeElement($qualifyConfig);
                }
                /** @var Closure(list<Round>):void $removeFromRounds */
                $removeFromRounds($round->getChildren());
            }
        };
        $removeFromRounds([$structure->getRootRound()]);
    }

    protected function getDefaultSportVariant(Sport $sport): SportVariant {
        if( $sport->getDefaultGameMode() === GameMode::AGAINST ) {
            return new AgainstSportVariant(
                $sport->getDefaultNrOfSidePlaces(),
                $sport->getDefaultNrOfSidePlaces(),
                $sport->getDefaultNrOfSidePlaces() > 1 ? 0 : 1,
                $sport->getDefaultNrOfSidePlaces() > 1 ? 1 : 0);
        } else if( $sport->getDefaultGameMode() === GameMode::SINGLE ) {
            return new SingleSportVariant(1, 1);
        }
        return new AllInOneGameSportVariant(1);
    }
}
