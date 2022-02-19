<?php namespace io\streams\compress\unittest;

use io\streams\InputStream;
use io\streams\compress\Bzip2InputStream;

class Bzip2InputStreamTest extends DecompressingInputStreamTest {

  /** Get filter */
  protected function filter(): string { return 'bzip2.*'; }

  /** Create fixture */
  protected function fixture(InputStream $wrapped): InputStream { return new Bzip2InputStream($wrapped); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return bzcompress($in, $level); }

  /** Erroneous data */
  protected function erroneous() {
    yield ["BZh61AY&SY\331<Plain data>"];
  }
}