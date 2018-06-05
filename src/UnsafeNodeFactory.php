<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class UnsafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    protected function createNodeFromArrayValue(array $array, ?Document $ownerDoc, int $flags): VectorNode
    {
        $i = 0;
        $packed = true;

        foreach ($array as $key => $value) {
            if ($key !== $i++) {
                $packed = false;
                break;
            }
        }

        return $packed
            ? $this->createArrayNodeFromPackedArray($array, $ownerDoc, $flags)
            : $this->createObjectNodeFromPropertyMap($array, $ownerDoc, $flags);
    }

    /**
     * @inheritdoc
     */
    protected function createNodeFromObjectValue(object $object, ?Document $ownerDoc, int $flags): ?Node
    {
        return $object instanceof \JsonSerializable
            ? $this->tryCreateNodeFromValue($object->jsonSerialize(), $ownerDoc, $flags)
            : $this->createObjectNodeFromPropertyMap($object, $ownerDoc, $flags);
    }
}
