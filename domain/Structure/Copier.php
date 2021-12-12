<?php

declare(strict_types=1);

namespace Sports\Structure;

use Exception;
use Sports\Competition;
use Sports\Competition\Sport as CompetitionSport;
use Sports\Place;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Planning\GameAmountConfig\Service as GameAmountConfigService;
use Sports\Poule;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;
use Sports\Qualify\AgainstConfig as AgainstQualifyConfig;
use Sports\Qualify\AgainstConfig\Service as AgainstQualifyConfigService;
use Sports\Qualify\Group as QualifyGroup;
use Sports\Qualify\Rule\Creator as QualifyRuleCreator;
use Sports\Round;
use Sports\Round\Number as RoundNumber;
use Sports\Score\Config as ScoreConfig;
use Sports\Score\Config\Service as ScoreConfigService;
use Sports\Structure;

class Copier
{
    private const SPORT_MAPPING_PROP_ID = 1;
    private const SPORT_MAPPING_PROP_NAME = 2;

    protected int $sportMappingProperty = self::SPORT_MAPPING_PROP_ID;

    public function __construct(
        private HorizontalPouleCreator $horPouleCreator,
        private QualifyRuleCreator $qualifyRuleCreator
    ) {
    }

    public function setSportMappingPropertyToName(): void
    {
        $this->sportMappingProperty = self::SPORT_MAPPING_PROP_NAME;
    }

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
            $newGameAmountConfig = $gameAmountConfigService->create($newCompetitionSport, $newRoundNumber);
            $newGameAmountConfig->setAmount($gameAmountConfig->getAmount());
            $newGameAmountConfig->setNrOfGamesPerPlaceMixed($gameAmountConfig->getNrOfGamesPerPlaceMixed());
        }
        $nextRoundNumber = $roundNumber->getNext();
        if ($nextRoundNumber !== null) {
            $this->copyRoundNumber($nextRoundNumber, $newRoundNumber->createNext());
        }
    }

    protected function getNewCompetitionSport(CompetitionSport $sourceCompetitionSport, Competition $newCompetition): CompetitionSport
    {
        foreach ($newCompetition->getSports() as $competitionSport) {
            if ($this->sportMappingProperty === self::SPORT_MAPPING_PROP_ID) {
                if ($competitionSport->getSport()->getId() === $sourceCompetitionSport->getSport()->getId()) {
                    return $competitionSport;
                }
            } else {
                if ($competitionSport->getSport()->getName() === $sourceCompetitionSport->getSport()->getName()) {
                    return $competitionSport;
                }
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
            array_values($round->getAgainstQualifyConfigs()->toArray())
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
        $this->qualifyRuleCreator->create($newRound, null, true);
    }

    /**
     * @param Round $newRound
     * @param list<Poule> $poules
     * @param list<ScoreConfig> $scoreConfigs
     * @param list<AgainstQualifyConfig> $againstQualifyConfigs
     */
    protected function copyRoundHelper(
        Round $newRound,
        array $poules,
        array $scoreConfigs,
        array $againstQualifyConfigs
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
        $againstQualifyConfigService = new AgainstQualifyConfigService();
        foreach ($againstQualifyConfigs as $againstQualifyConfig) {
            $newCompetitionSport = $this->getNewCompetitionSport(
                $againstQualifyConfig->getCompetitionSport(),
                $newRound->getCompetition()
            );
            $againstQualifyConfigService->copy($newCompetitionSport, $newRound, $againstQualifyConfig);
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
            new Place($newPoule, $place->getPlaceNr());
        }
    }
}
