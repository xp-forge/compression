<?php namespace io\streams\compress\unittest;

use io\streams\{Compression, MemoryInputStream, MemoryOutputStream, Streams};
use lang\IllegalArgumentException;
use test\verify\Runtime;
use test\{Assert, Expect, Before, Test, Values};

class CompressionTest {

  /** @return iterable */
  private function names() {

    // Included in this library
    yield ['gzip', 'gzip'];
    yield ['bzip2', 'bzip2'];
    yield ['brotli', 'brotli'];

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

  #[Test, Values(['none', 'NONE', 'identity', 'IDENTITY'])]
  public function none($name) {
    Assert::equals(Compression::$NONE, Compression::named($name));
  }

  #[Test, Values(['gzip', 'GZIP', '.gz', '.GZ']), Runtime(extensions: ['zlib'])]
  public function named_gzip($name) {
    Assert::equals('gzip', Compression::named($name)->name());
  }

  #[Test, Values(from: 'names')]
  public function algorithms_named($name, $expected) {
    Assert::equals($expected, Compression::algorithms()->named($name)->name());
  }

  #[Test, Values([['gzip', 'zlib'], ['bzip2', 'bzip2'], ['brotli', 'brotli']])]
  public function supported($compression, $extension) {
    Assert::equals(extension_loaded($extension), Compression::algorithms()->named($compression)->supported());
  }

  #[Test, Values([['gzip', 'gzip'], ['bzip2', 'bzip2'], ['brotli', 'br']])]
  public function token($compression, $expected) {
    Assert::equals($expected, Compression::algorithms()->named($compression)->token());
  }

  #[Test, Values([['gzip', '.gz'], ['bzip2', '.bz2'], ['brotli', '.br']])]
  public function extension($compression, $expected) {
    Assert::equals($expected, Compression::algorithms()->named($compression)->extension());
  }

  #[Test, Values(['', 'test']), Expect(IllegalArgumentException::class)]
  public function unknown($name) {
    Compression::algorithms()->named($name);
  }

  #[Test, Values(from: 'algorithms')]
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