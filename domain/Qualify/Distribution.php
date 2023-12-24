<?php

declare(strict_types=1);

namespace Sports\Qualify;

enum Distribution: string
{
    case HorizontalSnake = 'horizontalSnake';
    case Vertical = 'vertical';
}