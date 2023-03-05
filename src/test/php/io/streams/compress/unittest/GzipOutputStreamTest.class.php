<?php namespace io\streams\compress\unittest;

use io\streams\OutputStream;
use io\streams\compress\GzipOutputStream;
use test\verify\Runtime;

#[Runtime(extensions: ['zlib'])]
class GzipOutputStreamTest extends CompressingOutputStreamTest {

  /** Create fixture */
  protected function fixture(OutputStream $wrapped, int $level): OutputStream { return new GzipOutputStream($wrapped, $level); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return gzencode($in, $level); }

  /**
   * Asserts GZ-encoded data equals. Ignores the first 10 bytes - the
   * GZIP header, which will change every time due to current Un*x 
   * timestamp being embedded therein.
   *
   * @param  string $expected
   * @param  string $actual
   * @throws unittest.AssertionFailedError
   */
  protected function assertCompressedDataEquals($expected, $actual) {
    parent::assertCompressedDataEquals(substr($expected, 10), substr($actual, 10));
  }
}