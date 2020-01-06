<?php

namespace Games\Utils;

use Games\Utils\Mapable;
use Games\Utils\GameSerializable;
use Games\Utils\Comparable;

class Mapping implements GameSerializable, \JsonSerializable, \Countable, \IteratorAggregate{

    private $elements;

    public function __construct(){
        $this->elements = [];
    }

    public function add(Mapable $element){
        $this->elements[$element->getId()] = $element;
    }

    public function remove(Mapable $element){
        if (isset($this->elements[$element->getId()])) {
            unset($this->elements[$element->getId()]);
        }
    }

    public function contains(Mapable $element){
        return isset($this->elements[$element->getId()]);
    }

    public function get($id){
        if (isset($this->elements[$id])) {
            return $this->elements[$id];
        }
        return false;
    }

    public function keys(){
        return array_keys($this->elements);
    }

    public function values(){
        return array_values($this->elements);
    }

    public function next(Comparable $element){
        $isNext = $element->equals(end($this->elements));

        foreach($this->elements as $e){
            if($isNext)
                return $e;
            
            $isNext = $element->equals(end($e));
        }
        return false;
    }

    // Countable
    public function count(){
        return count($this->elements);
    }

    // IteratorAggregate
    public function getIterator(){
        return $this->$elements;
    }
    
    // JsonSerializable
    public function jsonSerialize()
    {
        $result = [];
        foreach($this->elements as $element){
            $result[$element->getId()] = $element->jsonSerialize();
        }
        return $result;
    }

    // GameSerializable
    public function gameSerialize(){
        $result = [];
        foreach($this->elements as $element){
            $result[$element->getId()] = $element->gameSerialize();
        }
        return $result;
    }
    
}
