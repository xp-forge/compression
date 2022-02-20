<?php namespace io\streams\compress\unittest;

use io\streams\{Compression, MemoryInputStream, MemoryOutputStream, Streams};
use lang\IllegalArgumentException;
use unittest\{Assert, Before, Test, Values};

class CompressionTest {

  /** @return iterable */
  private function names() {

    // Special member
    yield ['none', 'none'];
    yield ['identity', 'none'];
    yield ['NONE', 'none'];
    yield ['IDENTITY', 'none'];

    // Included in this library
    yield ['gzip', 'gzip'];
    yield ['bzip2', 'bzip2'];
    yield ['brotli', 'brotli'];
    yield ['GZIP', 'gzip'];
    yield ['BZIP2', 'bzip2'];
    yield ['BROTLI', 'brotli'];

    // File extensions
    yield ['.gz', 'gzip'];
    yield ['.bz2', 'bzip2'];
    yield ['.br', 'brotli'];

    // HTTP Content-Encoding aliases
    yield ['br', 'brotli'];    
  }

  /** @return iterable */
  private function algorithms() {
    yield [Compression::$NONE];
    foreach (Compression::algorithms()->supported() as $algorithm) {
      yield [$algorithm];
    }
  }

  #[Test]
  public function enumerating_included_algorithms() {
    $names= [];
    foreach (Compression::algorithms() as $name => $algorithm) {
      $names[]= $name;
    }
    Assert::equals(['gzip', 'bzip2', 'brotli'], $names);
  }

  #[Test]
  public function supported_algorithms() {
    Assert::instance('[:io.streams.compress.Algorithm]', iterator_to_array(Compression::algorithms()->supported()));
  }

  #[Test, Values('names')]
  public function named($name, $expected) {
    Assert::equals($expected, Compression::named($name)->name());
  }

  #[Test, Values(map: ['none' => 'core', 'gzip' => 'zlib', 'bzip2' => 'bzip2', 'brotli' => 'brotli'])]
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

  #[Test, Values('algorithms')]
  public function roundtrip($compressed) {
    $target= new MemoryOutputStream();

    $out= $compressed->create($target, Compression::DEFAULT);
    $out->write('Test');
    $out->close();

    $in= $compressed->open(new MemoryInputStream($target->bytes()));
    $result= Streams::readAll($in);
    $in->close();

    Assert::equals('Test', $result);
  }
}