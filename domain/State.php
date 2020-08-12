<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 8-6-19
 * Time: 19:39
 */

namespace Sports;

class State // extends \SplEnum
{
    const __default = self::Created;

    const Created = 1;
    const InProgress = 2;
    const Finished = 4;
    const Canceled = 8;
}
