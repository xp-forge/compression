<?php namespace io\streams\compress\unittest;

use io\IOException;
use io\streams\MemoryInputStream;
use io\streams\compress\ZStandardInputStream;
use test\verify\Runtime;
use test\{Assert, Test, Values};

#[Runtime(extensions: ['zstd'])]
class ZStandardInputStreamTest {

  /** @return iterable */
  private function compressable() {
    foreach ([ZSTD_COMPRESS_LEVEL_MIN, ZSTD_COMPRESS_LEVEL_DEFAULT, ZSTD_COMPRESS_LEVEL_MAX] as $level) {
      yield [$level, ''];
      yield [$level, 'Test'];
      yield [$level, "GIF89a\x14\x12\x77..."];
    }
  }

  #[Test]
  public function can_create() {
    new ZStandardInputStream(new MemoryInputStream(''));
  }

  #[Test]
  public function read_plain() {
    $in= new ZStandardInputStream(new MemoryInputStream('Test'));
    Assert::throws(IOException::class, function() use($in) {
      $in->read();
    });
    $in->close();
  }

  #[Test, Values(from: 'compressable')]
  public function read_compressed($level, $bytes) {
    $in= new ZStandardInputStream(new MemoryInputStream(zstd_compress($bytes, $level)));
    $read= $in->read();
    $rest= $in->available();
    $in->close();

    Assert::equals($bytes, $read);
    Assert::equals(0, $rest);
  }

  #[Test, Values([1, 8192, 16384])]
  public function read_all($length) {
    $bytes= random_bytes($length);
    $in= new ZStandardInputStream(new MemoryInputStream(zstd_compress($bytes)));

    $read= '';
    while ($in->available()) {
      $read.= $in->read();
    }
    $in->close();

    Assert::equals($bytes, $read);
  }
}