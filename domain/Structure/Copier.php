<?php
declare(strict_types=1);

namespace Sports\Structure;

use \Exception;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport ;
use Sports\Structure;
use Sports\Round\Number as RoundNumber;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Place;
use Sports\Poule;
use Sports\Score\Config as ScoreConfig;
use Sports\Qualify\AgainstConfig as QualifyAgainstConfig;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Planning\GameAmountConfig\Service as GameAmountConfigService;
use Sports\Qualify\AgainstConfig\Service as QualifyAgainstConfigService;

class Copier
{
    /**
     * @var Competition
     */
    protected $competition;

    public function __construct(Competition $competition)
    {
        $this->competition = $competition;
    }

    public function copy(Structure $structure): Structure
    {
        $planningConfigService = new PlanningConfigService();
        $gameAmountConfigService = new GameAmountConfigService();

        $firstRoundNumber = null;
        $rootRound = null;
        {
            /** @var RoundNumber|null $previousRoundNumber */
            $previousRoundNumber = null;
            foreach ($structure->getRoundNumbers() as $roundNumber) {
                $newRoundNumber = $previousRoundNumber !== null ? $previousRoundNumber->createNext() : new RoundNumber(
                    $this->competition,
                    $previousRoundNumber
                );
                if ($roundNumber->getPlanningConfig() !== null) {
                    $planningConfigService->copy($roundNumber->getPlanningConfig(), $newRoundNumber);
                }
                foreach ($roundNumber->getGameAmountConfigs() as $gameAmountConfig) {
                    $newCompetitionSport = $this->getNewCompetitionSport($gameAmountConfig->getCompetitionSport());
                    $gameAmountConfigService->copy($newCompetitionSport, $newRoundNumber, $gameAmountConfig->getAmount() );
                }

                if ($firstRoundNumber === null) {
                    $firstRoundNumber = $newRoundNumber;
                }
                $previousRoundNumber = $newRoundNumber;
            }
        }

        $rootRound = $this->copyRound($firstRoundNumber, $structure->getRootRound());
        $newStructure = new Structure($firstRoundNumber, $rootRound);
        $newStructure->setStructureNumbers();

        $postCreateService = new PostCreateService($newStructure);
        $postCreateService->create();
        return $newStructure;
    }

    protected function getNewCompetitionSport(CompetitionSport $sourceCompetitionSport): CompetitionSport
    {
        foreach ($this->competition->getSports() as $competitionSport) {
            if ($competitionSport->getSport()->getId() === $sourceCompetitionSport->getSport()->getId()) {
                return $competitionSport;
            }
        }
        throw new Exception("een sport kon niet gevonden worden", E_ERROR);
    }

    protected function copyRound(RoundNumber $roundNumber, Round $round, QualifyGroup $parentQualifyGroup = null): Round
    {
        $newRound = $this->copyRoundHelper($roundNumber,
                                           $round->getPoules()->toArray(),
                                           $round->getFirstScoreConfigs()->toArray(),
                                           $round->getQualifyAgainstConfigs()->toArray(),
                                           $parentQualifyGroup);

        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $newQualifyGroup = new QualifyGroup($newRound, $qualifyGroup->getWinnersOrLosers());
            $newQualifyGroup->setNumber($qualifyGroup->getNumber());
            // $qualifyGroup->setNrOfHorizontalPoules( $qualifyGroupSerialized->getNrOfHorizontalPoules() );
            $this->copyRound($roundNumber->getNext(), $qualifyGroup->getChildRound(), $newQualifyGroup);
        }
        return $newRound;
    }

    /**
     * @param RoundNumber $roundNumber
     * @param array|Poule[] $poules
     * @param array|ScoreConfig[] $scoreConfigs
     * @param array|QualifyAgainstConfig[] $qualifyAgainstConfigs
     * @param QualifyGroup|null $parentQualifyGroup
     * @return Round
     */
    protected function copyRoundHelper(
        RoundNumber $roundNumber,
        array $poules,
        array $scoreConfigs,
        array $qualifyAgainstConfigs,
        QualifyGroup $parentQualifyGroup = null
    ): Round {
        $round = new Round($roundNumber, $parentQualifyGroup);
        foreach ($poules as $poule) {
            $this->copyPoule($round, $poule->getNumber(), $poule->getPlaces()->toArray());
        }
        $scoreConfigService = new ScoreConfigService();
        foreach ($scoreConfigs as $scoreConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport($scoreConfig->getCompetitionSport());
            $scoreConfigService->copy($newCompetitionSport, $round, $scoreConfig);
        }
        $qualifyAgainstConfigService = new QualifyAgainstConfigService();
        foreach ($qualifyAgainstConfigs as $qualifyAgainstConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport($qualifyAgainstConfig->getCompetitionSport());
            $qualifyAgainstConfigService->copy($newCompetitionSport, $round, $qualifyAgainstConfig);
        }
        return $round;
    }

    /**
     * @param Round $round
     * @param int $number
     * @param array|Place[] $places
     *
     * @throws Exception
     *
     * @return void
     */
    protected function copyPoule(Round $round, int $number, array $places): void
    {
        $poule = new Poule($round, $number);
        foreach ($places as $place) {
            new Place($poule, $place->getNumber());
        }
    }

//    protected function getSportFromCompetition(CompetitionSport $competitionSport, Competition $competition): Sport
//    {
//        $foundSports = $competition->getSports()->filter(
//            function (CompetitionSport $competitionSportIt) use ($competitionSport): bool {
//                return $competitionSportIt->getSport()->getName() === $competitionSport->getSport()->getName();
//            }
//        );
//        if ($foundSports->count() !== 1) {
//            throw new Exception("Er kon geen overeenkomende sport worden gevonden", E_ERROR);
//        }
//        return $foundSports->first();
//    }
}
