<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

final class SafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    public function createNodeFromValue($value, ?Document $doc = null): Node
    {
        try {
            if (null !== $node = $this->createScalarOrNullNodeFromValue($value, $doc)) {
                return $node;
            }

            if (\is_object($value)) {
                return $this->createObjectNodeFromPropertyMap($value, $doc);
            }

            if (\is_array($value)) {
                return $this->createArrayNodeFromPackedArray($value, $doc);
            }
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new \Error('Unexpected ' . \get_class($e) . ": {$e->getMessage()}", 0, $e);
        }
        //@codeCoverageIgnoreEnd

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
