<?php

namespace Sports\Competition;

enum CompetitionSportFromToMapStrategy: int
{
    case ById = 1;
    case ByProperties = 2;
}
