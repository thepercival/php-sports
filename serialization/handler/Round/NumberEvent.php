<?php


namespace Sports\SerializationHandler\Round;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Sports\Round\Number as RoundNumber;
use Sports\Sport\ScoreConfig as SportScoreConfig;

class NumberEvent implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
                'class' => 'Sports\Round\Number', // if no class, subscribe to every serialization
                'format' => 'json', // optional format
                'priority' => 0, // optional priority
            )
        );
    }

    public function onPreSerialize( PreSerializeEvent $event)
    {
        /** @var RoundNumber $roundNumber */
        $roundNumber = $event->getObject();

        $filtered = $roundNumber->getSportScoreConfigs()->filter(function (SportScoreConfig $config): bool {
            return $config->isFirst();
        });
        $roundNumber->getSportScoreConfigs()->clear();
        foreach( $filtered as $firstSportScoreConfig ) {
            $roundNumber->getSportScoreConfigs()->add( $firstSportScoreConfig );
        }
    }
}
