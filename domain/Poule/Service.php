<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 17-3-17
 * Time: 13:44
 */

//namespace Sports\Poule;
//
//use Sports\Round;
//use Sports\Poule;
//use Sports\Place;
//use Sports\Poule\Repository as PouleRepository;
//use Sports\Place\Repository as PoulePlaceRepository;
//use Sports\Competitor\Repository as CompetitorRepository;
//
//class Service
//{
//    /**
//     * @var PouleRepository
//     */
//    protected $repos;
//    /**
//     * @var PoulePlaceRepository
//     */
//    protected $poulePlaceRepos;
//    /**
//     * @var CompetitorRepository
//     */
//    protected $competitorRepos;
//
//    /**
//     * Service constructor.
//     * @param Repository $repos
//     * @param PoulePlaceRepository $poulePlaceRepos
//     * @param CompetitorRepository $competitorRepos
//     */
//    public function __construct(
//        PouleRepository $repos,
//        PoulePlaceRepository $poulePlaceRepos,
//        CompetitorRepository $competitorRepos )
//    {
//        $this->repos = $repos;
//        $this->poulePlaceRepos = $poulePlaceRepos;
//        $this->competitorRepos = $competitorRepos;
//    }
//
//    public function create( Round $round, int $number, int $nrOfPlaces = null ): Poule
//    {
//        $poule = new Poule( $round, $number );
//        if( $nrOfPlaces !== null ) {
//            if ( $nrOfPlaces === 0 ) {
//                throw new \Exception("een poule moet minimaal 1 plek hebben", E_ERROR);
//            }
//            for( $placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++ ){
//                $pouleplace = new PoulePlace( $poule, $placeNr );
//            }
//        }
//        return $poule;
//    }
//
//    /**
//     * @param Round $round
//     * @param int $number
//     * @param array $placesSer
//     * @throws \Exception
//     */
//    public function createFromSerialized( Round $round, int $number, array $placesSer )
//    {
//        $poule = $this->create( $round, $number );
//        foreach( $placesSer as $placeSer ) {
//            $pouleplace = new PoulePlace( $poule, $placeSer->getNumber() );
//            if ( $placeSer->getCompetitor() === null ){
//                continue;
//            }
//            $competitor = $this->competitorRepos->find( $placeSer->getCompetitor()->getId() );
//            $pouleplace->setCompetitor($competitor);
//        }
//    }
//
////    /**
////     * @param Competitor $competitor
////     * @param $name
////     * @param Association $association
////     * @param null $abbreviation
////     * @return mixed
////     * @throws \Exception
////     */
////    public function edit( Competitor $competitor, $name, Association $association, $abbreviation = null )
////    {
////        $competitorWithSameName = $this->repos->findOneBy( array('name' => $name ) );
////        if ( $competitorWithSameName !== null and $competitorWithSameName !== $competitor ){
////            throw new \Exception("de bondsnaam ".$name." bestaat al", E_ERROR );
////        }
////
////        $competitor->setName($name);
////        $competitor->setAbbreviation($abbreviation);
////        $competitor->setAssociation($association);
////    }
////
//
//    /*protected function removeGames( Poule $poule )
//    {
//        $games = $poule->getGames();
//        while( $games->count() > 0 ) {
//            $game = $games->first();
//            $games->removeElement( $game );
//            // $this->scoreRepos->remove($game);
//        }
//    }*/
//}
