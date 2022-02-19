<?php namespace io\streams\compress\unittest;

use io\streams\OutputStream;
use io\streams\compress\Bzip2OutputStream;

class Bzip2OutputStreamTest extends CompressingOutputStreamTest {

  /** Get filter */
  protected function filter(): string { return 'bzip2.*'; }

  /** Create fixture */
  protected function fixture(OutputStream $wrapped, int $level): OutputStream { return new Bzip2OutputStream($wrapped, $level); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return bzcompress($in, $level); }
}