<?php namespace io\streams\compress\unittest;

use io\streams\OutputStream;
use io\streams\compress\Bzip2OutputStream;
use test\verify\Runtime;

#[Runtime(extensions: ['bz2'])]
class Bzip2OutputStreamTest extends CompressingOutputStreamTest {

  /** Create fixture */
  protected function fixture(OutputStream $wrapped, int $level): OutputStream { return new Bzip2OutputStream($wrapped, $level); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return bzcompress($in, $level); }
}