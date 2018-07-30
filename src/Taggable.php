<?php

namespace DaveRandom\Jom;

interface Taggable
{
    function hasTagValue(string $key): bool;

    /**
     * @return mixed
     */
    function getTagValue(string $key);

    /**
     * @param mixed $value
     */
    function setTagValue(string $key, $value): void;

    function removeTagValue(string $key): void;
}
