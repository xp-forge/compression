<?php namespace io\streams\compress\unittest;

use io\streams\MemoryInputStream;
use io\streams\compress\BrotliInputStream;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

#[Runtime(extensions: ['brotli'])]
class BrotliInputStreamTest {

  /** @return iterable */
  private function compressed() {
    foreach ([1, 6, 11] as $level) {
      yield [$level, ''];
      yield [$level, 'Test'];
      yield [$level, "GIF89a\x14\x12\x77..."];
    }
  }

  #[Test]
  public function can_create() {
    new BrotliInputStream(new MemoryInputStream(''));
  }

  #[Test]
  public function read_plain() {
    $in= new BrotliInputStream(new MemoryInputStream('Test'));
    $read= $in->read();
    $in->close();

    Assert::equals('', $read);
  }

  #[Test, Values(from: 'compressed')]
  public function read_compressed($level, $bytes) {
    $in= new BrotliInputStream(new MemoryInputStream(brotli_compress($bytes, $level)));
    $read= $in->read();
    $rest= $in->available();
    $in->close();

    Assert::equals($bytes, $read);
    Assert::equals(0, $rest);
  }

  #[Test, Values([1, 8192, 16384])]
  public function read_all($length) {
    $bytes= random_bytes($length);
    $in= new BrotliInputStream(new MemoryInputStream(brotli_compress($bytes)));

    $read= '';
    while ($in->available()) {
      $read.= $in->read();
    }
    $in->close();

    Assert::equals($bytes, $read);
  }
}