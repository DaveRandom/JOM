<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\CloneForbiddenException;

final class NodeListIterator implements \Iterator
{
    public const INACTIVE = -1;
    public const ACTIVE = 1;

    /** @var Node|null */
    private $firstNode;

    /** @var callable|null */
    private $activityStateChangeNotifier;

    /** @var int */
    private $activityState = self::INACTIVE;

    /** @var Node|null */
    private $currentNode;

    public function __construct(?Node $firstNode, ?callable $activityStateChangeNotifier = null)
    {
        $this->firstNode = $firstNode;
        $this->activityStateChangeNotifier = $activityStateChangeNotifier;
    }

    /**
     * @throws CloneForbiddenException
     */
    public function __clone()
    {
        throw new CloneForbiddenException(self::class . ' instance cannot be cloned');
    }

    private function notifyActivityStateChange(int $newState): void
    {
        if ($this->activityState === $newState) {
            return;
        }

        $this->activityState = $newState;

        if ($this->activityStateChangeNotifier !== null) {
            ($this->activityStateChangeNotifier)($this->activityState);
        }
    }

    public function __destruct()
    {
        $this->notifyActivityStateChange(self::INACTIVE);
    }

    public function current(): ?Node
    {
        return $this->currentNode;
    }

    public function next(): void
    {
        if ($this->currentNode !== null) {
            $this->currentNode = $this->currentNode->getNextSibling();
        }
    }

    public function key()
    {
        return $this->currentNode !== null
            ? $this->currentNode->getKey()
            : null;
    }

    public function valid(): bool
    {
        if ($this->currentNode !== null) {
            return true;
        }

        $this->notifyActivityStateChange(self::INACTIVE);

        return false;
    }

    public function rewind(): void
    {
        $this->notifyActivityStateChange(self::ACTIVE);

        $this->currentNode = $this->firstNode;
    }
}
