<?php namespace io\streams\compress;

use IteratorAggregate, Traversable;
use lang\{Value, IllegalArgumentException};
use util\Objects;

/**
 * Holds a list of compression algorithms
 * 
 * @test  io.streams.compress.unittest.AlgorithmsTest
 */
class Algorithms implements IteratorAggregate, Value {
  private $set= [], $lookup= [];

  /**
   * Adds the given algorithms. Overwrites algorithms with the same name
   * if present!
   */
  public function add(Algorithm... $algorithms): self {
    foreach ($algorithms as $algorithm) {
      $name= $algorithm->name();
      $this->set[$name]= $algorithm;
      $this->lookup[$algorithm->token()]= $this->lookup[$algorithm->extension()]= $name;
    }
    return $this;
  }

  /**
   * Finds a given algorithm by lookup, which may be either the name,
   * the HTTP Content-Encoding token or the file extension. If nothing
   * is found, `null` is returned.
   * 
   * @return ?io.streams.compress.Algorithm
   */
  public function find(string $lookup) {
    return $this->set[$lookup] ?? (($name= $this->lookup[$lookup] ?? null) ? $this->set[$name] : null);
  }

  /**
   * Like find, but raises an exception if nothing is found.
   *
   * @throws  lang.IllegalArgumentException
   */
  public function named(string $lookup): Algorithm {
    if ($algorithm= $this->find($lookup)) return $algorithm;

    throw new IllegalArgumentException('Unknown compression algorithm "'.$lookup.'"');
  }

  /**
   * Removes the given algorithm. Returns `false` if the algorithm was
   * not included in this set.
   *
   * @param  string|io.streams.compress.Algorithm $target
   */
  public function remove($target): bool {
    $algorithm= $target instanceof Algorithm ? $this->set[$target->name()] ?? null : $this->find($target);
    if (null === $algorithm) return false;

    unset($this->lookup[$algorithm->token()], $this->lookup[$algorithm->extension()]);
    unset($this->set[$algorithm->name()]);
    return true;
  }

  /** Iterates over algorithms, name => instance */
  public function getIterator(): Traversable {
    yield from $this->set;
  }

  /** Iterates over supported algorithms, name => instance */
  public function supported(): Traversable {
    foreach ($this->set as $name => $algorithm) {
      if ($algorithm->supported()) yield $name => $algorithm;
    }
  }

  /** Returns HTTP accept header for supported algorithms */
  public function accept(): string {
    $accept= '';
    foreach ($this->set as $algorithm) {
      if ($algorithm->supported()) $accept.= ", {$algorithm->token()}";
    }
    return substr($accept, 2);
  }

  /** @return string */
  public function hashCode() {
    return 'CA'.implode('&', array_keys($this->set));
  }

  /** @return string */
  public function toString() {
    if (empty($this->set)) return nameof($this).'{}';

    $s= nameof($this)."@{\n";
    foreach ($this->set as $algorithm) {
      $s.= '  '.$algorithm->toString()."\n";
    }
    return $s.'}';
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare(array_keys($this->set), array_keys($value->set)) : 1;
  }
}