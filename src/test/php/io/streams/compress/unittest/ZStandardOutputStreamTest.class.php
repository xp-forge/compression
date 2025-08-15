<?php namespace io\streams\compress\unittest;

use io\streams\MemoryOutputStream;
use io\streams\compress\ZStandardOutputStream;
use lang\IllegalArgumentException;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

#[Runtime(extensions: ['zstd'])]
class ZStandardOutputStreamTest {

  #[Test]
  public function can_create() {
    new ZStandardOutputStream(new MemoryOutputStream());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function using_invalid_compression_level() {
    new ZStandardOutputStream(new MemoryOutputStream(), -1);
  }

  #[Test, Values([1, 3, 22])]
  public function write($level) {
    $out= new MemoryOutputStream();

    $fixture= new ZStandardOutputStream($out, $level);
    $fixture->write('Hello');
    $fixture->write(' ');
    $fixture->write('World');
    $fixture->close();

    Assert::equals('Hello World', zstd_uncompress($out->bytes()));
  }
}