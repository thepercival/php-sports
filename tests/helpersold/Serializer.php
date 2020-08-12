<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-1-19
 * Time: 11:58
 */

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Handler\HandlerRegistry;

use Sports\SerializationHandler\Round as RoundSerializationHandler;
use Sports\SerializationHandler\Qualify\Group as QualifyGroupSerializationHandler;
use Sports\SerializationHandler\Round\Number as RoundNumberSerializationHandler;
use Sports\SerializationHandler\Structure as StructureSerializationHandler;

function getSerializer(): \JMS\Serializer\Serializer
{
    $apiVersion = 2;

    $serializerBuilder = SerializerBuilder::create()->setDebug(true);

    $serializerBuilder->setPropertyNamingStrategy(new SerializedNameAnnotationStrategy(new IdenticalPropertyNamingStrategy()));

    $serializerBuilder->setSerializationContextFactory(function () use ($apiVersion) {
        return SerializationContext::create()
            ->setGroups(array('Default'))
            ->setVersion($apiVersion);
    });
    $serializerBuilder->setDeserializationContextFactory(function () use ($apiVersion) {
        return DeserializationContext::create()
            ->setGroups(array('Default'))
            ->setVersion($apiVersion);
    });
    $serializerBuilder->addMetadataDir(__DIR__.'/../../serialization/yml', 'Voetbal');

    $serializerBuilder->configureHandlers(function (HandlerRegistry $registry) {
        $registry->registerSubscribingHandler(new StructureSerializationHandler());
        $registry->registerSubscribingHandler(new RoundNumberSerializationHandler());
        $registry->registerSubscribingHandler(new RoundSerializationHandler());
        $registry->registerSubscribingHandler(new QualifyGroupSerializationHandler());
    });

    $serializerBuilder->addDefaultHandlers();

    return $serializerBuilder->build();
}
