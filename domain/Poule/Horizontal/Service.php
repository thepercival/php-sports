<?php
declare(strict_types=1);

namespace Sports\Poule\Horizontal;

use Sports\Qualify\Group as QualifyGroup;
use Sports\Round;
use Sports\Place;
use Sports\Poule\Horizontal as HorizontalPoule;
use Sports\Poule\Horizontal\Creator as HorizontalPouleCreator;

class Service
{
    /**
     * @var list<int>
     */
    private array $winnersAndLosers;

    public function __construct(private Round $round, int|null $winnersOrLosers = null)
    {
        if ($winnersOrLosers === null) {
            $this->winnersAndLosers = [QualifyGroup::WINNERS, QualifyGroup::LOSERS];
        } else {
            $this->winnersAndLosers = [$winnersOrLosers];
        }
    }

    public function recreate(): void
    {
        $this->remove();
        $this->create();
    }

    round->removeHorizontalPoules
    round->addHorizontalPoules


    protected function remove(): void
    {
        foreach ($this->winnersAndLosers as $winnersOrLosers) {
            $horizontalPoules = &$this->round->getHorizontalPoules($winnersOrLosers);
            while (count($horizontalPoules) > 0) {
                $horizontalPoule = array_pop($horizontalPoules);

                $places = &$horizontalPoule->getPlaces();
                while (count($places) > 0) {
                    $place = array_pop($places);
                    $place->setHorizontalPoule($winnersOrLosers, null);
                }
            }
        }
    }

    protected function create(): void
    {
        foreach ($this->winnersAndLosers as $winnersOrLosers) {
            $this->createRoundHorizontalPoules($winnersOrLosers);
        }
    }

    /**
     * @param int $winnersOrLosers
     * @return list<HorizontalPoule>
     */
    protected function createRoundHorizontalPoules(int $winnersOrLosers): array
    {
        $horizontalPoules = $this->round->getHorizontalPoules2($winnersOrLosers);

        $placesHorizontalOrdered = $this->getPlacesHorizontal();
        if ($winnersOrLosers === QualifyGroup::LOSERS) {
            $placesHorizontalOrdered = array_reverse($placesHorizontalOrdered);
        }

        $nrOfPoules = $this->round->getPoules()->count();

        $horPoulePlaces = array_splice($placesHorizontalOrdered, 0, $nrOfPoules);
        while(count($horPoulePlaces) > 0)
        {
            $foundHorizontalPoule = new HorizontalPoule($this->round, count($horizontalPoules) + 1);
            $horPoulePlaces = array_splice($placesHorizontalOrdered, 0, $nrOfPoules);
        }
        // pak de breedte van de ronde en splice deze van de lijst en geef mee aan
       //  constructor van horizontalpoule

        foreach ($placesOrderedByPlaceNumber as $placeIt) {
            $filteredHorizontalPoules = array_filter($horizontalPoules, function ($horizontalPoule) use ($placeIt,$winnersOrLosers): bool {
                foreach ($horizontalPoule->getPlaces() as $poulePlaceIt) {
                    $poulePlaceNrIt = $poulePlaceIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $poulePlaceNrIt = ($poulePlaceIt->getPoule()->getPlaces()->count() + 1) - $poulePlaceNrIt;
                    }
                    $placeNrIt = $placeIt->getNumber();
                    if ($winnersOrLosers === QualifyGroup::LOSERS) {
                        $placeNrIt = ($placeIt->getPoule()->getPlaces()->count() + 1) - $placeNrIt;
                    }
                    if ($poulePlaceNrIt === $placeNrIt) {
                        return true;
                    }
                }
                return false;
            });

            $foundHorizontalPoule = array_shift($filteredHorizontalPoules);
            if ($foundHorizontalPoule === null) {
                $foundHorizontalPoule = new HorizontalPoule($this->round, count($horizontalPoules) + 1);
                $horizontalPoules[] = $foundHorizontalPoule;
            }
            // $placeIt->setHorizontalPoule($winnersOrLosers, $foundHorizontalPoule);
        }
        return $horizontalPoules;
    }

    /**
     * @return list<Place>
     */
    protected function getPlacesHorizontal(): array
    {
        $places = [];
        foreach ($this->round->getPoules() as $poule) {
            $places = array_merge($places, $poule->getPlaces()->toArray());
        }
        uasort($places, function (Place $placeA, Place $placeB) {
            if ($placeA->getNumber() > $placeB->getNumber()) {
                return 1;
            }
            if ($placeA->getNumber() < $placeB->getNumber()) {
                return -1;
            }
            if ($placeA->getPoule()->getNumber() > $placeB->getPoule()->getNumber()) {
                return 1;
            }
            if ($placeA->getPoule()->getNumber() < $placeB->getPoule()->getNumber()) {
                return -1;
            }
            return 0;
        });
        return array_values($places);
    }

    /**
     * @param list<HorizontalPoule> $roundHorizontalPoules
     * @param list<HorizontalPouleCreator> $horizontalPouleCreators
     * @return void
     */
    public function updateQualifyGroups(
        array $roundHorizontalPoules,
        array $horizontalPouleCreators
    ): void {
        foreach ($horizontalPouleCreators as $creator) {
            $creator->getQualifyGroup()->resetHorizontalPoules();
            $qualifiersAdded = 0;
            while ($qualifiersAdded < $creator->getNrOfQualifiers()) {
                $roundHorizontalPoule = array_shift($roundHorizontalPoules);
                if ($roundHorizontalPoule === null) {
                    throw new \Exception('no horizontalpoules found', E_ERROR);
                }
                $creator->getQualifyGroup()->addHorirzontalPoule($roundHorizontalPoule);
                $qualifiersAdded += count($roundHorizontalPoule->getPlaces2());
            }
        }
        foreach ($roundHorizontalPoules as $roundHorizontalPoule) {
            $roundHorizontalPoule->setQualifyGroup(null);
        }
    }
}
