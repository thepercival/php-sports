<?php

//namespace Sports\Round;
//
//use Sports\Competition;
//use Sports\Round;
//use Sports\Round\CompetitionRepository as RoundRepository;
//use Sports\Poule\CompetitionRepository as PouleRepository;
//use Sports\Poule;
//use Sports\Place;
//use Sports\Round\Number as RoundNumber;
//use Sports\Qualify\Group as QualifyGroup;
//
//class CompetitionEditor
//{
//    /**
//     * @var RoundRepository
//     */
//    protected $repos;
//    /**
//     * @var Poule\CompetitionEditor
//     */
//    protected $pouleService;
//    /**
//     * @var PouleRepository
//     */
//    protected $pouleRepos;
//
//    /**
//     * CompetitionEditor constructor.
//     * @param CompetitionRepository $repos
//     * @param Poule\CompetitionEditor $pouleService
//     * @param PouleRepository $pouleRepos
//     */
//    public function __construct(
//        RoundRepository $repos,
//        Poule\CompetitionEditor $pouleService,
//        PouleRepository $pouleRepos
//    )
//    {
//        $this->repos = $repos;
//        $this->pouleService = $pouleService;
//        $this->pouleRepos = $pouleRepos;
//    }
//
//    public function create(
//        Number $roundNumber,
//        array $nrOfPlacesPerPoule,
//        QualifyGroup $parentQualifyGroup = null ): Round
//    {
//        $round = new Round($roundNumber, $parentQualifyGroup);
//        foreach( $nrOfPlacesPerPoule as $idx => $nrOfPlaces  ) {
//            $this->pouleService->create( $round, $idx + 1, $nrOfPlaces );
//        }
//        return $round;
//    }
//

//
//    public function createByOptions(
//        RoundNumber $roundNumber,
//        int  $nrOfPlaces,
//        int  $nrOfPoules,
//        QualifyGroup $parentQualifyGroup = null
//    ): Round
//    {
//        if ($nrOfPlaces < 2) {
//            throw new \Exception("het aantal plekken voor een nieuwe ronde moet minimaal 2 zijn", E_ERROR );
//        }
//        if ($nrOfPoules < 1) {
//            throw new \Exception("het aantal poules voor een nieuwe ronde moet minimaal 1 zijn", E_ERROR );
//        }
//
//        $round = $this->create( $roundNumber, [], $parentQualifyGroup );
//        $this->createPoules( $round, $nrOfPlaces, $nrOfPoules );
//
//        return $round;
//    }
//
//    private function createPoules( Round $round, int $nrOfPlaces, int $nrOfPoules )
//    {
//        $nrOfPlacesPerPoule = $this->getNrOfPlacesPerPoule( $nrOfPlaces, $nrOfPoules);
//
//        $pouleNumber = 1;
//        while ($nrOfPlaces > 0) {
//            $nrOfPlacesToAdd = $nrOfPlaces < $nrOfPlacesPerPoule ? $nrOfPlaces : $nrOfPlacesPerPoule;
//            $this->pouleService->create( $round, $pouleNumber++, $nrOfPlacesToAdd );
//            $nrOfPlaces -= $nrOfPlacesPerPoule;
//        }
//    }
//
//    public function getNrOfPlacesPerPoule(int $nrOfPlaces, int $nrOfPoules): int {
//        $nrOfPlaceLeft = ($nrOfPlaces % $nrOfPoules);
//        if ($nrOfPlaceLeft === 0) {
//            return $nrOfPlaces / $nrOfPoules;
//        }
//        return (($nrOfPlaces - $nrOfPlaceLeft) / $nrOfPoules) + 1;
//    }
//
//    /**
//     * @param Round $round
//     */
////    public function remove( Round $round )
////    {
////        if( $round->getParent() !== null ) {
////            $round->getParent()->getChildRounds()->removeElement($round);
////            $round->getNumber()->getRounds()->removeElement($round);
////        }
////        return $this->repos->getEM()->remove($round);
////    }
//
//    public function getDefaultNrOfPoules(int $nrOfPlaces, int $min = null, int $max = null): int {
//        if( $min === null ) {
//            $min = Competition::MIN_COMPETITORS;
//        }
//        if( $max === null ) {
//            $max = Competition::MAX_COMPETITORS;
//        }
//        if ($nrOfPlaces < $min ) {
//            return 1;
//        }
//        if ($nrOfPlaces > $max) {
//            return 9;
//        }
//
//        $defaultNrOfPlaces = [
//            null, null, /* 2 */
//            1, // 2
//            1,
//            1,
//            1,
//            2, // 6
//            1,
//            2,
//            3,
//            2, // 10
//            2,
//            3,
//            3,
//            3,
//            3,
//            4,
//            4,
//            4, // 18
//            4,
//            5,
//            5,
//            5,
//            5,
//            6, // 24
//            5,
//            6,
//            9, // 27
//            7,
//            6,
//            6,
//            7,
//            8, // 32
//            6,
//            6,
//            7,
//            6,
//            7,
//            7,
//            7,
//            8
//        ];
//        $defaultNrOfPlaces[$nrOfPlaces];
//    }
//}
