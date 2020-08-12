<?php


namespace Sports\SerializationHandler\Round;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Round\Number as RoundNumber;
use Sports\Sport\ScoreConfig as SportScoreConfig;

class NumberEvent implements \JMS\Serializer\EventDispatcher\EventSubscriberInterface
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

    public function onPreSerialize(\JMS\Serializer\EventDispatcher\PreSerializeEvent $event)
    {
        /** @var RoundNumber $roundNumber */
        $roundNumber = $event->getObject();

        $roundNumber->setSportScoreConfigs(
            $roundNumber->getSportScoreConfigs()->filter(function (SportScoreConfig $config) {
                return $config->isFirst();
            })
        );
    }
}
