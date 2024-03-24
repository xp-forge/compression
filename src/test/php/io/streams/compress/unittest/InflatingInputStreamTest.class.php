<?php namespace io\streams\compress\unittest;

use io\streams\InputStream;
use io\streams\compress\InflatingInputStream;
use test\verify\Runtime;

#[Runtime(extensions: ['zlib'])]
class InflatingInputStreamTest extends DecompressingInputStreamTest {

  /** Get stream */
  protected function fixture(InputStream $wrapped): InputStream { return new InflatingInputStream($wrapped); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return gzdeflate($in, $level); }

  /** Return erroneous data */
  protected function erroneous() { return []; }
}