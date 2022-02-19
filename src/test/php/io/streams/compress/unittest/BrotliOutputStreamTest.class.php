<?php namespace io\streams\compress\unittest;

use io\streams\MemoryOutputStream;
use io\streams\compress\BrotliOutputStream;
use lang\IllegalArgumentException;
use unittest\{Assert, Before, Test, Values, PrerequisitesNotMetError};

class BrotliOutputStreamTest {

  #[Before]
  public function verify() {
    if (!extension_loaded('brotli')) {
      throw new PrerequisitesNotMetError('Brotli extension missing');
    }
  }

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