<?php namespace io\streams\compress\unittest;

use io\streams\OutputStream;
use io\streams\compress\DeflatingOutputStream;
use test\verify\Runtime;

#[Runtime(extensions: ['zlib'])]
class DeflatingOutputStreamTest extends CompressingOutputStreamTest {

  /** Get stream */
  protected function fixture(OutputStream $wrapped, int $level): OutputStream {
    return new DeflatingOutputStream($wrapped, $level);
  }

  /** Compress data */
  protected function compress(string $in, int $level): string {
    return gzdeflate($in, $level);
  }
}