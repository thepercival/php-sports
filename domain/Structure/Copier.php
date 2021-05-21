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
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;

class Copier
{
    public function __construct(
        private HorizontalPouleCreator $horPouleCreator,
        private QualifyRuleCreator $qualifyRuleCreator
    ){}

    public function copy(Structure $structure, Competition $newCompetition): Structure
    {
        $newFirstRoundNumber = new RoundNumber($newCompetition);
        $this->copyRoundNumber($structure->getFirstRoundNumber(), $newFirstRoundNumber);
        $newRootRound = new Round($newFirstRoundNumber);
        $this->copyRound($structure->getRootRound(), $newRootRound);
        $newStructure = new Structure($newFirstRoundNumber, $newRootRound);
        return $newStructure;
    }

    protected function copyRoundNumber(RoundNumber $roundNumber, RoundNumber $newRoundNumber): void
    {
        $planningConfigService = new PlanningConfigService();
        $gameAmountConfigService = new GameAmountConfigService();

        $planningConfig = $roundNumber->getPlanningConfig();
        if ($planningConfig !== null) {
            $planningConfigService->copy($planningConfig, $newRoundNumber);
        }
        foreach ($roundNumber->getGameAmountConfigs() as $gameAmountConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport(
                $gameAmountConfig->getCompetitionSport(),
                $newRoundNumber->getCompetition()
            );
            $gameAmountConfigService->create($newCompetitionSport, $newRoundNumber);
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->copyRoundNumber($nextRoundNumber, $newRoundNumber->createNext());
        }
    }

    protected function getNewCompetitionSport(CompetitionSport $sourceCompetitionSport, Competition $newCompetition): CompetitionSport
    {
        foreach ($newCompetition->getSports() as $competitionSport) {
            if ($competitionSport->getSport()->getId() === $sourceCompetitionSport->getSport()->getId()) {
                return $competitionSport;
            }
        }
        throw new Exception("een sport kon niet gevonden worden", E_ERROR);
    }

    protected function copyRound(Round $round, Round $newRound): void
    {
        $this->copyRoundHelper(
            $newRound,
            array_values($round->getPoules()->toArray()),
            array_values($round->getFirstScoreConfigs()->toArray()),
            array_values($round->getQualifyAgainstConfigs()->toArray())
        );
        $this->horPouleCreator->create($newRound);

        $newNextRoundNumber = $newRound->getNumber()->getNext();
        if ($newNextRoundNumber === null) {
            return;
        }
        foreach ($round->getQualifyGroups() as $qualifyGroup) {
            $newQualifyGroup = new QualifyGroup($newRound, $qualifyGroup->getTarget(), $newNextRoundNumber);
            $newQualifyGroup->setNumber($qualifyGroup->getNumber());
            // $qualifyGroup->setNrOfHorizontalPoules( $qualifyGroupSerialized->getNrOfHorizontalPoules() );
            $this->copyRound($qualifyGroup->getChildRound(), $newQualifyGroup->getChildRound());
        }
        $this->qualifyRuleCreator->create($newRound);
    }

    /**
     * @param Round $newRound
     * @param list<Poule> $poules
     * @param list<ScoreConfig> $scoreConfigs
     * @param list<QualifyAgainstConfig> $qualifyAgainstConfigs
     */
    protected function copyRoundHelper(
        Round $newRound,
        array $poules,
        array $scoreConfigs,
        array $qualifyAgainstConfigs
    ): void {
        foreach ($poules as $poule) {
            $this->copyPoule($newRound, $poule->getNumber(), array_values($poule->getPlaces()->toArray()));
        }
        $scoreConfigService = new ScoreConfigService();
        foreach ($scoreConfigs as $scoreConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport(
                $scoreConfig->getCompetitionSport(),
                $newRound->getCompetition()
            );
            $scoreConfigService->copy($newCompetitionSport, $newRound, $scoreConfig);
        }
        $qualifyAgainstConfigService = new QualifyAgainstConfigService();
        foreach ($qualifyAgainstConfigs as $qualifyAgainstConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport(
                $qualifyAgainstConfig->getCompetitionSport(),
                $newRound->getCompetition()
            );
            $qualifyAgainstConfigService->copy($newCompetitionSport, $newRound, $qualifyAgainstConfig);
        }
    }

    /**
     * @param Round $newRound
     * @param int $number
     * @param list<Place> $places
     */
    protected function copyPoule(Round $newRound, int $number, array $places): void
    {
        $newPoule = new Poule($newRound, $number);
        foreach ($places as $place) {
            new Place($newPoule, $place->getNumber());
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
