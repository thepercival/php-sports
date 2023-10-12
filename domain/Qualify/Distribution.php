<?php

declare(strict_types=1);

namespace Sports\Qualify;

enum Distribution: int
{
    case HorizontalSnake = 0;
    case Vertical = 1;
}