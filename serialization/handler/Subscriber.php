<?php

declare(strict_types=1);

namespace Sports\SerializationHandler;

use JMS\Serializer\Handler\HandlerRegistry;
use Sports\SerializationHandler\Game\AgainstGameHandler;
use Sports\SerializationHandler\Game\AgainstGamePlaceHandler;
use Sports\SerializationHandler\Game\TogetherGameHandler;
use Sports\SerializationHandler\Game\TogetherGamePlaceHandler;
use Sports\SerializationHandler\Round\NumberHandler as RoundNumberHandler;
use Sports\SerializationHandler\Qualify\GroupHandler as QualifyGroupHandler;
use Sports\SerializationHandler\Planning\ConfigHandler as PlanningConfigHandler;


class Subscriber
{
    public function __construct(protected DummyCreator $dummyCreator)
    {
    }

    public function subscribeHandlers(HandlerRegistry $registry): void
    {
        $registry->registerSubscribingHandler(new StructureHandler());
        $registry->registerSubscribingHandler(new RoundNumberHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new QualifyGroupHandler());
        $registry->registerSubscribingHandler(new RoundHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new PouleHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new PlanningConfigHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new AgainstGameHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new AgainstGamePlaceHandler());
        $registry->registerSubscribingHandler(new TogetherGameHandler($this->dummyCreator));
        $registry->registerSubscribingHandler(new TogetherGamePlaceHandler());
        $registry->registerSubscribingHandler(new StructureLocationPlaceHandler($this->dummyCreator));
    }
}
