<?php

declare(strict_types=1);

namespace Sports\Qualify;

enum QualifyDistribution: string
{
    case HorizontalSnake = 'horizontalSnake';
    case Vertical = 'vertical';
}