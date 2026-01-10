<?php

declare(strict_types=1);

namespace Sports\TestHelper;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Handler\HandlerRegistry;
use JMS\Serializer\SerializerInterface as JMSSerializer;

use Sports\SerializationHandler\DummyCreator;
use Sports\SerializationHandler\RoundHandler as RoundSerializationHandler;
use Sports\SerializationHandler\Round\NumberHandler as RoundNumberSerializationHandler;
use Sports\SerializationHandler\StructureHandler as StructureSerializationHandler;

final class Serializer
{
    public function getSerializer(): JMSSerializer
    {
        $apiVersion = "2";

        $serializerBuilder = SerializerBuilder::create()->setDebug(true);

        $serializerBuilder->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new IdenticalPropertyNamingStrategy()));

        $serializerBuilder->setSerializationContextFactory(
            function () use ($apiVersion): SerializationContext {
                return SerializationContext::create()
                    ->setGroups(array('Default'))
                    ->setVersion($apiVersion);
            }
        );
        $serializerBuilder->setDeserializationContextFactory(
            function () use ($apiVersion): DeserializationContext {
                return DeserializationContext::create()
                    ->setGroups(array('Default'))
                    ->setVersion($apiVersion);
            }
        );
        $serializerBuilder->addMetadataDir(__DIR__.'/../../serialization/yml', 'Sports');
        $dummyCreator = new DummyCreator();
        $serializerBuilder->configureHandlers(
            function (HandlerRegistry $registry) use ($dummyCreator): void {
                $registry->registerSubscribingHandler(new StructureSerializationHandler());
                $registry->registerSubscribingHandler(new RoundNumberSerializationHandler($dummyCreator));
                $registry->registerSubscribingHandler(new RoundSerializationHandler($dummyCreator));
                // $registry->registerSubscribingHandler(new QualifyGroupSerializationHandler());
            }
        );

        $serializerBuilder->addDefaultHandlers();

        return $serializerBuilder->build();
    }
}
