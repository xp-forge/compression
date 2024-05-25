<?php namespace io\streams;

use io\streams\compress\{Algorithm, Algorithms, None, Brotli, Bzip2, Gzip};
use lang\MethodNotImplementedException;

/**
 * Compression algorithms registry and lookup
 *
 * @see   https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 * @see   https://en.wikipedia.org/wiki/HTTP_compression#Content-Encoding_tokens
 * @test  io.streams.compress.unittest.CompressionTest
 */
abstract class Compression {
  const FASTEST   = -1;
  const DEFAULT   = -2;
  const STRONGEST = -3;

  public static $NONE;
  private static $algorithms;

  static function __static() {
    self::$NONE= new None();

    // Register known algorithms included in this library
    self::$algorithms= (new Algorithms())->add(new Gzip(), new Bzip2(), new Brotli());
  }

  /**
   * Returns registered compression algorithms, not including `Compression::$NONE`.
   */
  public static function algorithms(): Algorithms {
    return self::$algorithms;
  }

  /**
   * Returns a compression for a given name. Accepts enumeration members
   * in upper- and lowercase, common file extensions as well as the tokens
   * used in HTTP Content-Encoding.
   *
   * @throws  lang.IllegalArgumentException when unknown
   * @throws  lang.MethodNotImplementedException when unsupported
   */
  public static function named(string $name): Algorithm {
    $lookup= strtolower($name);
    if ('none' === $lookup || 'identity' === $lookup) return self::$NONE;

    $algorithm= self::$algorithms->named($lookup);
    if ($algorithm->supported()) return $algorithm;

    throw new MethodNotImplementedException('Unsupported compression algorithm', $name);
  }

  /**
   * Selects a compression for a given list of preferred algorithms. Returns
   * the first supported one, or NULL if none in the given list is supported.
   *
   * @param   iterable $preferred
   * @return  ?io.streams.compress.Algorithm
   */
  public static function select($preferred) {
    foreach ($preferred as $name) {
      $lookup= strtolower($name);
      if ('none' === $lookup || 'identity' === $lookup) return self::$NONE;

      $algorithm= self::$algorithms->find($lookup);
      if ($algorithm && $algorithm->supported()) return $algorithm;
    }
    return null;
  }
}