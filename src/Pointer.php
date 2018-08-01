<?php declare(strict_types=1);

namespace DaveRandom\Jom;

use DaveRandom\Jom\Exceptions\InvalidPointerException;

final class Pointer
{
    /** @var string[] */
    private $path = [];

    /** @var int|null */
    private $relativeLevels = null;

    /** @var bool */
    private $keyLookup = false;

    /** @var string */
    private $string;

    /**
     * @throws InvalidPointerException
     */
    private static function decodePath(string $path): array
    {
        if ($path === '') {
            return [];
        }

        if ($path[0] !== '/') {
            throw new InvalidPointerException('JSON pointer path must be the empty string or begin with /');
        }

        $result = [];

        foreach (\explode('/', \substr($path, 1)) as $component) {
            $result[] = \str_replace(['~1', '~0'], ['/', '~'], $component);
        }

        return $result;
    }

    private static function encodePath(array $path): string
    {
        $result = '';

        foreach ($path as $component) {
            $result .= '/' . \str_replace(['~', '/'], ['~0', '~1'], $component);
        }

        return $result;
    }

    private static function splitRelativePointerComponents(string $pointer): array
    {
        return \preg_match('/^(0|[1-9][0-9]*)($|[^0-9].*)/i', $pointer, $match)
            ? [$match[2], (int)$match[1]]
            : [$pointer, null];
    }

    /**
     * @param string[] $path
     * @throws InvalidPointerException
     */
    private static function validatePointerComponents(array $path, ?int $relativeLevels, ?bool $isKeyLookup): void
    {
        if ($relativeLevels < 0) {
            throw new InvalidPointerException('Relative levels cannot be negative');
        }

        if ($isKeyLookup && !empty($path)) {
            throw new InvalidPointerException('Key lookup is invalid with non-empty path');
        }

        if ($isKeyLookup && $relativeLevels === null) {
            throw new InvalidPointerException('Key lookup is invalid for absolute pointers');
        }
    }

    /**
     * @throws InvalidPointerException
     */
    private function resolveAncestor(int $levels): Pointer
    {
        $result = clone $this;
        $result->string = null;

        if ($levels === 0) {
            return $result;
        }

        $count = \count($this->path) - $levels;

        if ($count < 0) {
            if ($this->relativeLevels === null) {
                throw new InvalidPointerException('Cannot reference ancestors above root of absolute pointer');
            }

            $result->relativeLevels = $this->relativeLevels - $count;
        }

        $result->path = $count > 0
            ? \array_slice($this->path, 0, $count)
            : [];

        return $result;
    }

    /**
     * @throws InvalidPointerException
     */
    public static function createFromString(string $pointer): Pointer
    {
        $result = new self();

        [$path, $result->relativeLevels] = self::splitRelativePointerComponents($pointer);

        $result->keyLookup = $result->relativeLevels !== null && $path === '#';

        if (!$result->keyLookup) {
            $result->path = self::decodePath($path);
        }

        return $result;
    }

    private function __construct() { }

    /**
     * @param string[] $path
     * @throws InvalidPointerException
     */
    public static function createFromParameters(array $path, ?int $relativeLevels = null, ?bool $isKeyLookup = false): Pointer
    {
        self::validatePointerComponents($path, $relativeLevels, $isKeyLookup);

        $result = new self();

        $result->relativeLevels = $relativeLevels;
        $result->keyLookup = $isKeyLookup ?? false;

        foreach ($path as $component) {
            $result->path[] = (string)$component;
        }

        return $result;
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function getRelativeLevels(): ?int
    {
        return $this->relativeLevels;
    }

    public function isRelative(): bool
    {
        return $this->relativeLevels !== null;
    }

    public function isKeyLookup(): bool
    {
        return $this->keyLookup;
    }

    /**
     * Resolve another pointer using this instance as a base and return the resulting pointer.
     *
     * If the reference pointer is absolute, it is returned unmodified.
     *
     * If the reference pointer is relative, it is used to generate a pointer that resolves to the same target node when
     * starting from location on which the current pointer is based. The result will be relative if the current pointer
     * is relative. The result will be a key lookup if the reference pointer is a key lookup.
     *
     * Examples:
     *
     * base:   /a/b/c
     * other:  1/d/e
     * result: /a/b/d/e
     *
     * base:   3/a/b/c
     * other:  4/d/e
     * result: 2/d/e
     *
     * @param Pointer|string $other
     * @throws InvalidPointerException
     */
    public function resolvePointer($other): Pointer
    {
        if (!($other instanceof self)) {
            $other = self::createFromString((string)$other);
        }

        if ($other->relativeLevels === null) {
            return $other;
        }

        $result = $this->resolveAncestor($other->relativeLevels);

        if (!empty($other->path)) {
            \array_push($result->path, ...$other->path);
        }

        $result->keyLookup = $other->keyLookup;

        if ($result->relativeLevels === $this->relativeLevels
            && $result->keyLookup === $this->keyLookup
            && $result->path !== $this->path) {
            return $this;
        }

        $result->string = null;

        return $result;
    }

    public function getPointerForChild($key, ...$keys): Pointer
    {
        $result = clone $this;

        $result->string = null;
        $result->keyLookup = false;

        \array_push($result->path, (string)$key, ...\array_map('strval', $keys));

        return $result;
    }

    /**
     * @throws InvalidPointerException
     */
    public function getPointerForAncestor(int $levels = 1): Pointer
    {
        if ($levels < 1) {
            throw new InvalidPointerException("Ancestor levels must be positive");
        }

        return self::resolveAncestor($levels);
    }

    public function __toString(): string
    {
        if (isset($this->string)) {
            return $this->string;
        }

        $this->string = '';

        if ($this->relativeLevels !== null) {
            $this->string .= $this->relativeLevels;
        }

        $this->string .= $this->keyLookup
            ? '#'
            : self::encodePath($this->path);

        return $this->string;
    }
}
