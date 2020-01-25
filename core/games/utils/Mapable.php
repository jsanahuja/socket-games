<?php

namespace Games\Utils;

use Games\Utils\GameSerializable;
use Games\Utils\Comparable;

interface Mapable extends GameSerializable, \JsonSerializable, Comparable{
    public function getId();
}