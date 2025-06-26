<?php

namespace Sports\SerializationHandler\Round;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use Sports\Round\Number as RoundNumber;
use Sports\Score\ScoreConfig as ScoreConfig;

class NumberEvent implements EventSubscriberInterface
{
    /**
     * @psalm-return list<array<string, int|string>>
     */
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

    public function onPreSerialize(PreSerializeEvent $event): void
    {
//        /** @var RoundNumber $roundNumber */
//        $roundNumber = $event->getObject();
//
//        $filtered = $roundNumber->getScoreConfigs()->filter(function (ScoreConfig $config): bool {
//            return $config->isFirst();
//        });
//        $roundNumber->getScoreConfigs()->clear();
//        foreach( $filtered as $firstScoreConfig ) {
//            $roundNumber->getScoreConfigs()->add( $firstScoreConfig );
//        }
    }
}
