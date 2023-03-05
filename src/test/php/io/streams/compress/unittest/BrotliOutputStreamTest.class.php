<?php namespace io\streams\compress\unittest;

use io\streams\MemoryOutputStream;
use io\streams\compress\BrotliOutputStream;
use lang\IllegalArgumentException;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

#[Runtime(extensions: ['brotli'])]
class BrotliOutputStreamTest {

  #[Test]
  public function can_create() {
    new BrotliOutputStream(new MemoryOutputStream());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function using_invalid_compression_level() {
    new BrotliOutputStream(new MemoryOutputStream(), -1);
  }

  #[Test, Values([1, 6, 11])]
  public function write($level) {
    $out= new MemoryOutputStream();

    $fixture= new BrotliOutputStream($out, $level);
    $fixture->write('Hello');
    $fixture->write(' ');
    $fixture->write('World');
    $fixture->close();

    Assert::equals('Hello World', brotli_uncompress($out->bytes()));
  }
}