<?php namespace io\streams\compress\unittest;

use io\IOException;
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
    yield ['zstandard', 'zstandard'];

    // File extensions
    yield ['.gz', 'gzip'];
    yield ['.bz2', 'bzip2'];
    yield ['.br', 'brotli'];
    yield ['.zstd', 'zstandard'];

    // HTTP Content-Encoding aliases
    yield ['br', 'brotli'];    
    yield ['zstd', 'zstandard'];
  }

  /** @return iterable */
  private function algorithms() {
    yield [Compression::$NONE];
    foreach (Compression::algorithms()->supported() as $algorithm) {
      yield [$algorithm];
    }
  }

  /** @return iterable */
  private function erroneous() {
    $algorithms= Compression::algorithms();

    $gzip= $algorithms->named('gzip');
    if ($gzip->supported()) {
      yield [$gzip, "\037\213\b\000\000\000\000\000\000\n<Plain data>"];
    }

    // PHP 7.4RC1 is the first version to handle reading errors correctly, see
    // https://github.com/php/php-src/commit/d59aac58b3e7da7ad01a194fe9840d89725ea229
    $bzip2= $algorithms->named('bzip2');
    if ($bzip2->supported() && PHP_VERSION_ID >= 70400) {
      yield [$bzip2, "BZh61AY&SY\331<Plain data>"];
    }

    $zstd= $algorithms->named('zstd');
    if ($zstd->supported()) {
      yield [$zstd, "<Plain data>"];
    }
  }

  #[Test]
  public function enumerating_included_algorithms() {
    $names= [];
    foreach (Compression::algorithms() as $name => $algorithm) {
      $names[]= $name;
    }
    Assert::equals(['gzip', 'bzip2', 'brotli', 'zstandard'], $names);
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

  #[Test, Values([['gzip', 'zlib'], ['bzip2', 'bz2'], ['brotli', 'brotli'], ['zstandard', 'zstd']])]
  public function supported($compression, $extension) {
    Assert::equals(extension_loaded($extension), Compression::algorithms()->named($compression)->supported());
  }

  #[Test, Values([['gzip', 'gzip'], ['bzip2', 'bzip2'], ['brotli', 'br'], ['zstandard', 'zstd']])]
  public function token($compression, $expected) {
    Assert::equals($expected, Compression::algorithms()->named($compression)->token());
  }

  #[Test, Values([['gzip', '.gz'], ['bzip2', '.bz2'], ['brotli', '.br'], ['zstandard', '.zstd']])]
  public function extension($compression, $expected) {
    Assert::equals($expected, Compression::algorithms()->named($compression)->extension());
  }

  #[Test, Values(['', 'test']), Expect(IllegalArgumentException::class)]
  public function unknown($name) {
    Compression::algorithms()->named($name);
  }

  #[Test, Values(from: 'algorithms')]
  public function compress_roundtrip($compressed) {
    $bytes= $compressed->compress('Test', Compression::DEFAULT);
    $result= $compressed->decompress($bytes);

    Assert::equals('Test', $result);
  }

  #[Test, Values(from: 'algorithms')]
  public function streams_roundtrip($compressed) {
    $target= new MemoryOutputStream();

    $out= $compressed->create($target, Compression::DEFAULT);
    $out->write('Test');
    $out->close();

    $in= $compressed->open(new MemoryInputStream($target->bytes()));
    $result= Streams::readAll($in);
    $in->close();

    Assert::equals('Test', $result);
  }

  #[Test, Values(from: 'erroneous'), Expect(IOException::class)]
  public function decompress_erroneous($compressed, $bytes) {
    $compressed->decompress($bytes);
  }
}