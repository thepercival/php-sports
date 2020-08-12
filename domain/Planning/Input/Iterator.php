<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 24-10-17
 * Time: 9:44
 */

namespace Sports\Planning\Input;

use SportsPlanning\HelperTmp;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Resources;
use SportsHelpers\Range;
use Sports\Place\Range as PlaceRange;
use Sports\Planning\Config\Service as PlanningConfigService;
use Sports\Sport;
use Sports\Structure\Service as StructureService;

class Iterator
{
    /**
     * @var PlaceRange
     */
    protected $rangePlaces;
    /**
     * @var Range
     */
    protected $rangePoules;
    /**
     * @var Range
     */
    protected $rangeNrOfSports;
    /**
     * @var Range
     */
    protected $rangeNrOfReferees;
    /**
     * @var Range
     */
    protected $rangeNrOfFields;
    /**
     * @var Range
     */
    protected $rangeNrOfHeadtohead;
    /**
     * @var int
     */
    protected $maxFieldsMultipleSports = 6;
    /**
     * @var StructureService
     */
    protected $structureService;
    /**
     * @var PlanningConfigService
     */
    protected $planningConfigService;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var int
     */
    protected $nrOfPoules;
    /**
     * @var int
     */
    protected $nrOfSports;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfFields;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var bool
     */
    protected $teamup;
    /**
     * @var int
     */
    protected $selfReferee;

    /**
     * @var bool
     */
    protected $incremented;
    /**
     * @var int
     */
    protected $nrOfGamesPlaces;

    public function __construct(
        PlaceRange $rangePlaces,
        Range $rangePoules,
        Range $rangeNrOfSports,
        Range $rangeNrOfFields,
        Range $rangeNrOfReferees,
        Range $rangeNrOfHeadtohead
    ) {
        $this->rangePlaces = $rangePlaces;
        $this->rangePoules = $rangePoules;
        $this->rangeNrOfSports = $rangeNrOfSports;
        $this->rangeNrOfFields = $rangeNrOfFields;
        $this->rangeNrOfReferees = $rangeNrOfReferees;
        $this->rangeNrOfHeadtohead = $rangeNrOfHeadtohead;
        $this->maxFieldsMultipleSports = 6;

        $this->structureService = new StructureService([$rangePlaces]);
        $this->planningConfigService = new PlanningConfigService();
        // @TODO SHOULD BE IN ITERATION
        $this->nrOfGamesPlaces = Sport::TEMPDEFAULT;

        $this->incremented = false;
        $this->init();
    }

    protected function getSportConfig(int $nrOfSports, int $nrOfFields): array
    {
        $sports = [];
        $nrOfFieldsPerSport = (int)ceil($nrOfFields / $nrOfSports);
        for ($sportNr = 1; $sportNr <= $nrOfSports; $sportNr++) {
            $sports[] = ["nrOfFields" => $nrOfFieldsPerSport, "nrOfGamePlaces" => Sport::TEMPDEFAULT];
            $nrOfFields -= $nrOfFieldsPerSport;
            if (($nrOfFieldsPerSport * ($nrOfSports - $sportNr)) > $nrOfFields) {
                $nrOfFieldsPerSport--;
            }
        }
        return $sports;
    }

    protected function init()
    {
        $this->initNrOfPlaces();
    }

    protected function initNrOfPlaces()
    {
        $this->nrOfPlaces = $this->rangePlaces->max;
        $this->initNrOfPoules();
    }

    protected function initNrOfPoules()
    {
        $this->nrOfPoules = $this->rangePoules->min;
        $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
            $this->nrOfPlaces,
            $this->nrOfPoules,
            true
        );
        while ($nrOfPlacesPerPoule > $this->rangePlaces->getPlacesPerPouleRange()->max) {
            $this->nrOfPoules++;
            $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
                $this->nrOfPlaces,
                $this->nrOfPoules,
                true
            );
        }
        $this->initNrOfSports();
    }

    protected function initNrOfSports()
    {
        $this->nrOfSports = $this->rangeNrOfSports->min;
        $this->initNrOfFields();
    }

    protected function initNrOfFields()
    {
        if ($this->rangeNrOfFields->min >= $this->nrOfSports) {
            $this->nrOfFields = $this->rangeNrOfFields->min;
        } else {
            $this->nrOfFields = $this->nrOfSports;
        }
        $this->initNrOfReferees();
    }

    protected function initNrOfReferees()
    {
        $this->nrOfReferees = $this->rangeNrOfReferees->min;
        $this->initNrOfHeadtohead();
    }

    protected function initNrOfHeadtohead()
    {
        $this->nrOfHeadtohead = $this->rangeNrOfHeadtohead->min;
        $this->initTeamup();
    }

    protected function initTeamup()
    {
        $this->teamup = false;
        $this->initSelfReferee();
    }

    protected function initSelfReferee()
    {
        $this->selfReferee = PlanningInput::SELFREFEREE_DISABLED;
    }


    //     return [json_decode(json_encode(["selfReferee" => $selfReferee, "teamup" => $teamup]))];
    public function increment(): ?PlanningInput
    {
        if ($this->incremented === false) {
            $this->incremented = true;
            return $this->createInput();
        }

        if ($this->incrementValue() === false) {
            return null;
        }

        $planningInput = $this->createInput();

        $maxNrOfRefereesInPlanning = $planningInput->getMaxNrOfBatchGames(
            Resources::FIELDS + Resources::PLACES
        );
        if ($this->nrOfReferees < $this->nrOfFields && $this->nrOfReferees > $maxNrOfRefereesInPlanning) {
            if ($this->incrementNrOfFields() === false) {
                return null;
            }
            return $this->createInput();
        }

        $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
            Resources::REFEREES + Resources::PLACES
        );
        if ($this->nrOfFields < $this->nrOfReferees && $this->nrOfFields > $maxNrOfFieldsInPlanning) {
            if ($this->incrementNrOfSports() === false) {
                return null;
            }
            return $this->createInput();
        }

        return $planningInput;
    }

    protected function createInput(): PlanningInput
    {
        $structureConfig = $this->structureService->getStructureConfig($this->nrOfPlaces, $this->nrOfPoules);
        $sportConfig = $this->getSportConfig($this->nrOfSports, $this->nrOfFields);
        return new PlanningInput(
            $structureConfig,
            $sportConfig,
            $this->nrOfReferees,
            $this->teamup,
            $this->selfReferee,
            $this->nrOfHeadtohead
        );
    }

    public function incrementValue(): bool
    {
        return $this->incrementSelfReferee();
    }

    protected function incrementSelfReferee(): bool
    {
        if ($this->nrOfReferees > 0 || $this->selfReferee === PlanningInput::SELFREFEREE_SAMEPOULE) {
            return $this->incrementTeamup();
        }

        $nrOfGamePlaces = (new HelperTmp())->getNrOfGamePlaces($this->nrOfGamesPlaces, $this->teamup, false);
        $selfRefereeIsAvailable = $this->planningConfigService->canSelfRefereeBeAvailable(
            $this->nrOfPoules,
            $this->nrOfPlaces,
            $nrOfGamePlaces
        );
        if ($selfRefereeIsAvailable === false) {
            return $this->incrementTeamup();
        }
        if ($this->selfReferee === PlanningInput::SELFREFEREE_DISABLED) {
            if ($this->planningConfigService->canSelfRefereeOtherPoulesBeAvailable($this->nrOfPoules)) {
                $this->selfReferee = PlanningInput::SELFREFEREE_OTHERPOULES;
            } else {
                $this->selfReferee = PlanningInput::SELFREFEREE_SAMEPOULE;
            }
        } else {
            $selfRefereeSamePouleAvailable = $this->planningConfigService->canSelfRefereeSamePouleBeAvailable(
                $this->nrOfPoules,
                $this->nrOfPlaces,
                $nrOfGamePlaces
            );
            if (!$selfRefereeSamePouleAvailable) {
                return $this->incrementTeamup();
            }
            $this->selfReferee = PlanningInput::SELFREFEREE_SAMEPOULE;
        }
        return true;
    }

    protected function incrementTeamup(): bool
    {
        if ($this->teamup === true) {
            return $this->incrementNrOfHeadtohead();
        }
        $structureConfig = $this->structureService->getStructureConfig($this->nrOfPlaces, $this->nrOfPoules);
        $sportConfig = $this->getSportConfig($this->nrOfSports, $this->nrOfFields);
        $teamupAvailable = $this->planningConfigService->canTeamupBeAvailable($structureConfig, $sportConfig);
        if ($teamupAvailable === false) {
            return $this->incrementNrOfHeadtohead();
        }
        $this->teamup = true;
        $this->initSelfReferee();
        return true;
    }

    protected function incrementNrOfHeadtohead(): bool
    {
        if ($this->nrOfHeadtohead === $this->rangeNrOfHeadtohead->max) {
            return $this->incrementNrOfReferees();
            ;
        }
        $this->nrOfHeadtohead++;
        $this->initTeamup();
        return true;
    }

    protected function incrementNrOfReferees(): bool
    {
        $maxNrOfReferees = $this->rangeNrOfReferees->max;
        $maxNrOfRefereesByPlaces = (int)(ceil($this->nrOfPlaces / 2));
        if ($this->nrOfReferees >= $maxNrOfReferees || $this->nrOfReferees >= $maxNrOfRefereesByPlaces) {
            return $this->incrementNrOfFields();
            ;
        }
        $this->nrOfReferees++;
        $this->initNrOfHeadtohead();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        $maxNrOfFields = $this->rangeNrOfFields->max;
        $maxNrOfFieldsByPlaces = (int)(ceil($this->nrOfPlaces / 2));
        if ($this->nrOfFields >= $maxNrOfFields || $this->nrOfFields >= $maxNrOfFieldsByPlaces) {
            return $this->incrementNrOfSports();
            ;
        }
        $this->nrOfFields++;
        $this->initNrOfReferees();
        return true;
    }

    protected function incrementNrOfSports(): bool
    {
        if ($this->nrOfSports === $this->rangeNrOfSports->max) {
            return $this->incrementNrOfPoules();
            ;
        }
        $this->nrOfSports++;
        $this->initNrOfFields();
        return true;
    }

    protected function incrementNrOfPoules(): bool
    {
        if ($this->nrOfPoules === $this->rangePoules->max) {
            return $this->incrementNrOfPlaces();
        }
        $nrOfPlacesPerPoule = $this->structureService->getNrOfPlacesPerPoule(
            $this->nrOfPlaces,
            $this->nrOfPoules + 1,
            true
        );
        if ($nrOfPlacesPerPoule < $this->rangePlaces->getPlacesPerPouleRange()->min) {
            return $this->incrementNrOfPlaces();
        }

        $this->nrOfPoules++;
        $this->initNrOfSports();
        return true;
    }

    protected function incrementNrOfPlaces(): bool
    {
        if ($this->nrOfPlaces === $this->rangePlaces->min) {
            return false;
        }
        $this->nrOfPlaces--;
        $this->initNrOfPoules();
        return true;
    }





    /*if ($nrOfCompetitors === 6 && $nrOfPoules === 1 && $nrOfSports === 1 && $nrOfFields === 2
        && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
        $w1 = 1;
    } else*/ /*if ($nrOfCompetitors === 12 && $nrOfPoules === 2 && $nrOfSports === 1 && $nrOfFields === 4
            && $nrOfReferees === 0 && $nrOfHeadtohead === 1 && $teamup === false && $selfReferee === false ) {
            $w1 = 1;
        } else {
            continue;
        }*/

//        $multipleSports = count($sportConfig) > 1;
//        $newNrOfHeadtohead = $nrOfHeadtohead;
//        if ($multipleSports) {
//            //                                    if( count($sportConfig) === 4 && $sportConfig[0]["nrOfFields"] == 1 && $sportConfig[1]["nrOfFields"] == 1
//            //                                        && $sportConfig[2]["nrOfFields"] == 1 && $sportConfig[3]["nrOfFields"] == 1
//            //                                        && $teamup === false && $selfReferee === false && $nrOfHeadtohead === 1 && $structureConfig == [3]  ) {
//            //                                        $e = 2;
//            //                                    }
//            $newNrOfHeadtohead = $this->planningInputSerivce->getSufficientNrOfHeadtohead(
//                $nrOfHeadtohead,
//                min($structureConfig),
//                $teamup,
//                $selfReferee,
//                $sportConfig
//            );
//        }

//        $planningInput = new PlanningInput(
//            $structureConfig,
//            $sportConfig,
//            $nrOfReferees,
//            $teamup,
//            $selfReferee,
//            $newNrOfHeadtohead
//        );
//
//        if (!$multipleSports) {
//            $maxNrOfFieldsInPlanning = $planningInput->getMaxNrOfBatchGames(
//                Resources::REFEREES + Resources::PLACES
//            );
//            if ($nrOfFields > $maxNrOfFieldsInPlanning) {
//                return;
//            }
//        } else {
//            if ($nrOfFields > self::MAXNROFFIELDS_FOR_MULTIPLESPORTS) {
//                return;
//            }
//        }
}
