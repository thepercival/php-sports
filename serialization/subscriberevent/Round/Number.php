<?php

namespace Sports\SerializationSubscriberEvent\Round;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;

class Number implements EventSubscriberInterface
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
            ),
        );
    }

    public function onPreSerialize(PreSerializeEvent $event): void
    {
        // do something
        $x = $event;
    }
}
