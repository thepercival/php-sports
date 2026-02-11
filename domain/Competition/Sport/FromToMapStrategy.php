<?php

namespace Sports\Competition\Sport;

enum FromToMapStrategy: int
{
    case ById = 1;
    case ByProperties = 2;
}
