<?php declare(strict_types=1);

namespace DaveRandom\Jom;

trait TagData
{
    protected $tagData = [];

    final public function hasTagValue(string $key): bool
    {
        return \array_key_exists($key, $this->tagData);
    }

    /**
     * @return mixed
     */
    final public function getTagValue(string $key)
    {
        return $this->tagData[$key] ?? null;
    }

    /**
     * @param mixed $value
     */
    final public function setTagValue(string $key, $value): void
    {
        $this->tagData[$key] = $value;
    }

    final public function removeTagValue(string $key): void
    {
        unset($this->tagData[$key]);
    }
}
