<?php namespace io\streams;

use io\streams\compress\{Algorithm, Algorithms, None, Brotli, Bzip2, Gzip};
use lang\IllegalArgumentException;

/**
 * Compression algorithms registry and lookup
 *
 * @see   https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 * @see   https://en.wikipedia.org/wiki/HTTP_compression#Content-Encoding_tokens
 * @test  io.streams.compress.unittest.CompressionTest
 */
abstract class Compression {
  const FASTEST   = 0;
  const DEFAULT   = 1;
  const STRONGEST = 2;

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
   * @throws  lang.IllegalArgumentException
   */
  public static function named(string $name): Algorithm {
    $lookup= strtolower($name);
    if ('none' === $lookup || 'identity' === $lookup) {
      return self::$NONE;
    } else if ($algorithm= self::$algorithms->find($lookup)) {
      return $algorithm;
    }

    throw new IllegalArgumentException('Unknown compression algorithm "'.$name.'"');
  }
}