<?php


namespace Sports\SerializationSubscriberEvent\Round;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Context;

use Sports\Round as RoundBase;
use Sports\Poule;
use Sports\Place;
use Sports\Competitor;
use Sports\Qualify\Group as QualifyGroup;

class Number implements JMS\Serializer\EventDispatcher\EventSubscriberInterface
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
            ),
        );
    }

    public function onPreSerialize(JMS\Serializer\EventDispatcher\PreSerializeEvent $event)
    {
        // do something
        $x = $event;
    }
}
