<?php
declare(strict_types=1);

/**
 * Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2010 - 2024, Cake Development Corporation (https://www.cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace CakeDC\Auth\Authentication;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * TwoFactorProcessorCollection Collection
 */
class TwoFactorProcessorCollection implements IteratorAggregate, Countable
{
    /**
     * Processors list
     *
     * @var array<\CakeDC\Auth\Authentication\TwoFactorProcessorInterface>
     */
    protected $processors = [];

    /**
     * Constructor
     *
     * @param array<\CakeDC\Auth\Authentication\TwoFactorProcessorInterface> $processors The list of processors to add to the collection.
     */
    public function __construct(array $processors = [])
    {
        $this->addMany($processors);
    }

    /**
     * Remove all processors from the collection
     *
     * @return $this
     */
    public function clear()
    {
        $this->processors = [];

        return $this;
    }

    /**
     * Add multiple processors at once.
     *
     * @param array<\CakeDC\Auth\Authentication\TwoFactorProcessorInterface|string> $processors The list of processors to add to the collection.
     * @return $this
     */
    public function addMany(array $processors)
    {
        foreach ($processors as $processor) {
            if (is_string($processor) && is_subclass_of($processor, TwoFactorProcessorInterface::class)) {
                $processor = new $processor();
            }
            $this->add($processor);
        }

        return $this;
    }

    /**
     * Add a processor to the collection
     *
     * Processor will be keyed by their names.
     *
     * @param \CakeDC\Auth\Authentication\TwoFactorProcessorInterface $processor The processor to load.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function add(TwoFactorProcessorInterface $processor)
    {
        $this->processors[] = $processor;

        return $this;
    }

    /**
     * Implementation of IteratorAggregate.
     *
     * @return \Traversable
     * @psalm-return \Traversable<int, \CakeDC\Auth\Authentication\TwoFactorProcessorInterface|class-string>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->processors);
    }

    /**
     * Implementation of Countable.
     *
     * Get the number of processors in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->processors);
    }
}
