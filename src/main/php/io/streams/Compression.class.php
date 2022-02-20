<?php namespace io\streams;

use io\streams\compress\{
  GzipInputStream,
  GzipOutputStream,
  Bzip2InputStream,
  Bzip2OutputStream,
  BrotliInputStream,
  BrotliOutputStream
};
use lang\{Enum, IllegalArgumentException};

/**
 * Compression algorithms enumeration
 *
 * @see   https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 * @see   https://en.wikipedia.org/wiki/HTTP_compression#Content-Encoding_tokens
 * @test  io.streams.compress.unittest.CompressionTest
 */
abstract class Compression extends Enum {
  const FASTEST   = 0;
  const DEFAULT   = 1;
  const STRONGEST = 2;

  public static $NONE, $GZIP, $BZIP2, $BROTLI;

  static function __static() {
    self::$NONE= new class(0, 'NONE') extends Compression {
      public function supported(): bool { return true; }
      public function token(): string { return 'identity'; }
      public function extension(): string { return ''; }
      public function open($in) { return $in; }
      public function create($out, $method) { return $out; }
    };

    self::$GZIP= new class(1, 'GZIP') extends Compression {
      const LEVELS= [Compression::FASTEST => 1, Compression::DEFAULT => 6, Compression::STRONGEST => 9];

      public function supported(): bool { return extension_loaded('zlib'); }
      public function token(): string { return 'gzip'; }
      public function extension(): string { return '.gz'; }
      public function open($in) { return new GzipInputStream($in); }
      public function create($out, $method= Compression::DEFAULT) {
        return new GzipOutputStream($out, self::LEVELS[$method]);
      }
    };

    self::$BZIP2= new class(2, 'BZIP2') extends Compression {
      const LEVELS= [Compression::FASTEST => 1, Compression::DEFAULT => 4, Compression::STRONGEST => 9];

      public function supported(): bool { return extension_loaded('bz2'); }
      public function token(): string { return 'bzip2'; }
      public function extension(): string { return '.bz2'; }
      public function open($in) { return new Bzip2InputStream($in); }
      public function create($out, $method= Compression::DEFAULT) {
        return new Bzip2OutputStream($out, self::LEVELS[$method]);
      }
    };

    self::$BROTLI= new class(3, 'BROTLI') extends Compression {
      const LEVELS= [Compression::FASTEST => 1, Compression::DEFAULT => 11, Compression::STRONGEST => 11];

      public function supported(): bool { return extension_loaded('brotli'); }
      public function token(): string { return 'br'; }
      public function extension(): string { return '.br'; }
      public function open($in) { return new BrotliInputStream($in); }
      public function create($out, $method= Compression::DEFAULT) {
        return new BrotliOutputStream($out, self::LEVELS[$method]);
      }
    };
  }

  /** Returns whether this compression algorithm is supported */
  public abstract function supported(): bool;

  /** Returns token for use with HTTP Content-Encoding */
  public abstract function token(): string;

  /** Returns common file extension including "." */
  public abstract function extension(): string;

  /**
   * Returns compression algorithms supported in this setup, excluding
   * the "NONE" algorithm. May return an empty list!
   *
   * @return self[]
   */
  public static function algorithms() {
    $r= [];
    foreach (self::values() as $compression) {
      $compression->ordinal() && $compression->supported() && $r[]= $compression;
    }
    return $r;
  }

  /**
   * Returns a compression for a given name. Accepts enumeration members
   * in upper- and lowercase, common file extensions as well as the tokens
   * used in HTTP Content-Encoding
   *
   * @throws  lang.IllegalArgumentException
   */
  public static function named(string $name): self {
    switch (strtolower($name)) {
      case 'none': case 'identity': return self::$NONE;
      case 'gzip': case 'gz': case '.gz': return self::$GZIP;
      case 'bzip2': case 'bz2': case '.bz2': return self::$BZIP2;
      case 'brotli': case 'br': case '.br': return self::$BROTLI;
      default: throw new IllegalArgumentException('Unknown compression algorithm "'.$name.'"');
    }
  }
}