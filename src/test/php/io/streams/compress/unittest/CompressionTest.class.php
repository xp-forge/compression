<?php namespace io\streams\compress\unittest;

use io\streams\Compression;
use lang\IllegalArgumentException;
use unittest\{Assert, Test, Values};

class CompressionTest {

  /** @return iterable */
  private function names() {

    // Member names
    yield ['none', Compression::$NONE];
    yield ['gzip', Compression::$GZIP];
    yield ['bzip2', Compression::$BZIP2];
    yield ['brotli', Compression::$BROTLI];
    yield ['NONE', Compression::$NONE];
    yield ['GZIP', Compression::$GZIP];
    yield ['BZIP2', Compression::$BZIP2];
    yield ['BROTLI', Compression::$BROTLI];

    // Short names used in file extensions or HTTP Content-Encoding
    yield ['identity', Compression::$NONE];
    yield ['gz', Compression::$GZIP];
    yield ['bz2', Compression::$BZIP2];
    yield ['br', Compression::$BROTLI];
    yield ['.gz', Compression::$GZIP];
    yield ['.bz2', Compression::$BZIP2];
    yield ['.br', Compression::$BROTLI];
  }

  #[Test]
  public function algorithms() {
    Assert::instance('io.streams.Compression[]', Compression::algorithms());
  }

  #[Test, Values('names')]
  public function named($name, $expected) {
    Assert::equals($expected, Compression::named($name));
  }

  #[Test, Values(map: ['none' => 'core', 'gzip' => 'zlib', 'bzip2' => 'bz2', 'brotli' => 'brotli'])]
  public function supported($compression, $extension) {
    Assert::equals(extension_loaded($extension), Compression::named($compression)->supported());
  }

  #[Test, Values(map: ['none' => 'identity', 'gzip' => 'gzip', 'bzip2' => 'bzip2', 'brotli' => 'br'])]
  public function token($compression, $expected) {
    Assert::equals($expected, Compression::named($compression)->token());
  }

  #[Test, Values(map: ['none' => '', 'gzip' => '.gz', 'bzip2' => '.bz2', 'brotli' => '.br'])]
  public function extension($compression, $expected) {
    Assert::equals($expected, Compression::named($compression)->extension());
  }

  #[Test, Values(['', 'test']), Expect(IllegalArgumentException::class)]
  public function unknown($name) {
    Compression::named($name);
  }
}