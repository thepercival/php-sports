<?php

declare(strict_types=1);

namespace Sports\SerializationHandler\Round;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;
use Sports\Association;
use Sports\Competition;
use Sports\League;
use Sports\Round\Number as RoundNumber;
use Sports\Season;
use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\Handler;
use Sports\Planning\GameAmountConfig;
use Sports\Planning\Config as PlanningConfig;
use Sports\Competition\Sport as CompetitionSport;

class NumberHandler extends Handler implements SubscribingHandlerInterface
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    /**
     * @psalm-return list<array<string, int|string>>
     */
    public static function getSubscribingMethods(): array
    {
        return static::getDeserializationMethods(RoundNumber::class);
    }

    /**
     * @param JsonDeserializationVisitor $visitor
     * @param array<string, bool|RoundNumber|array> $fieldValue
     * @param array<string, array<string, RoundNumber>> $type
     * @param Context $context
     * @return RoundNumber
     */
    public function deserializeFromJson(
        JsonDeserializationVisitor $visitor,
        array $fieldValue,
        array $type,
        Context $context
    ): RoundNumber {
        $roundNumber = null;
        /** @var RoundNumber|null $previous */
        $previous = null;
        if (isset($fieldValue["previous"])) {
            $roundNumber = $fieldValue["previous"]->createNext();
        } else {
            $competition = $this->dummyCreator->createCompetition();
            $roundNumber = new RoundNumber($competition, $previous);
        }

        if (isset($fieldValue["planningConfig"])) {
            $fieldValue["planningConfig"]["roundNumber"] = $roundNumber;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "planningConfig",
                PlanningConfig::class
            );
        }

        if (isset($fieldValue["gameAmountConfigs"])) {
            foreach ($fieldValue["gameAmountConfigs"] as $arrGameAmountConfig) {
                $competitionSport = $this->dummyCreator->createCompetitionSport(
                    $roundNumber->getCompetition(),
                    (int) $arrGameAmountConfig["competitionSport"]["id"],
                    (int) $arrGameAmountConfig["competitionSport"]["sport"]["id"]
                );
                new GameAmountConfig(
                    $competitionSport,
                    $roundNumber,
                    $arrGameAmountConfig["amount"]
                );
            }
        }

        if (isset($fieldValue["next"])) {
            $fieldValue["next"]["previous"] = $roundNumber;
            $this->getProperty(
                $visitor,
                $fieldValue,
                "next",
                RoundNumber::class
            );
        }

        return $roundNumber;
    }


    //function postSerialize( Structure $structure, Competition $competition ) {
//    deserializeFromJson( $structure->getRootRound(), $structure->getFirstRoundNumber(), $competition );
//}
//
//    private function deserializeFromJson( Round $round, RoundNumber $roundNumber, Competition $competition, RoundNumber $previousRoundNumber = null ) {
//        $refCl = new \ReflectionClass($round);
//        $refClPropNumber = $refCl->getProperty("number");
//        $refClPropNumber->setAccessible(true);
//        $refClPropNumber->setValue($round, $roundNumber);
//        $refClPropNumber->setAccessible(false);
//        $roundNumber->setCompetition($competition);
//        $roundNumber->getRounds()->add($round);
//        $roundNumber->setPrevious( $previousRoundNumber );
//        foreach( $round->getPoules() as $poule ) {
//            $poule->setRound($round);
//            foreach( $poule->getPlaces() as $poulePlace ) {
//                $poulePlace->setPoule($poule);
//            }
//            if( $poule->getGames() === null ) {
//                $poule->setGames([]);
//            }
//            foreach( $poule->getGames() as $game ) {
//                foreach( $game->getPoulePlaces() as $gamePoulePlace ) {
//                    $gamePoulePlace->setPoulePlace($poule->getPlace($gamePoulePlace->getPoulePlaceNr()));
//                }
//                $game->setPoule($poule);
//                foreach ($game->getScores() as $gameScore) {
//                    $gameScore->setGame($game);
//                }
//            }
//        }
//        foreach( $round->getChildren() as $childRound ) {
//            $childRound->setParent($round);
//            postSerializeHelper( $childRound, $roundNumber->getNext(), $competition, $roundNumber );
//        }
//    }
}
