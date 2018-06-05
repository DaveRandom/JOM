<?php declare(strict_types=1);

namespace DaveRandom\Jom;

final class SafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    protected function createNodeFromArrayValue(?Document $ownerDoc, array $array, int $flags): VectorNode
    {
        return $this->createArrayNodeFromPackedArray($array, $ownerDoc, $flags);
    }

    /**
     * @inheritdoc
     */
    protected function createNodeFromObjectValue(?Document $ownerDoc, object $object, int $flags): ?Node
    {
        return $this->createObjectNodeFromPropertyMap($object, $ownerDoc, $flags);
    }
}
