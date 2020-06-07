<?php


namespace App\Services;


use FOS\RestBundle\Inflector\InflectorInterface;

class Inflector implements InflectorInterface
{
    public function pluralize($word)
    {
        // Don't pluralize
        return $word;
    }
}