<?php

namespace Sports\Game;

use Sports\Team;

interface Event
{
    public function getTeam(): Team;
}
