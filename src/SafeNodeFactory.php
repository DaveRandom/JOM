<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidNodeValueException;

final class SafeNodeFactory extends NodeFactory
{
    /**
     * @inheritdoc
     */
    public function createNodeFromValue($value, ?Document $doc, int $flags): ?Node
    {
        try {
            if (null !== $node = $this->createScalarOrNullNodeFromValue($value, $doc)) {
                return $node;
            }

            if (\is_object($value)) {
                return $this->createObjectNodeFromPropertyMap($value, $doc, $flags);
            }

            if (\is_array($value)) {
                return $this->createArrayNodeFromPackedArray($value, $doc, $flags);
            }

            if ($flags & Node::IGNORE_INVALID_VALUES) {
                return null;
            }
        } catch (InvalidNodeValueException $e) {
            throw $e;
        //@codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw unexpected($e);
        }
        //@codeCoverageIgnoreEnd

        throw new InvalidNodeValueException("Failed to create node from value of type '" . \gettype($value) . "'");
    }
}
