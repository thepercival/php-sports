<?php

namespace Sports\Priority;

interface Prioritizable
{
    public function getPriority(): int;

    public function setPriority(int $priority);
}