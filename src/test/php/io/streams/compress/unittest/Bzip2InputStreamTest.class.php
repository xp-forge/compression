<?php namespace io\streams\compress\unittest;

use io\streams\InputStream;
use io\streams\compress\Bzip2InputStream;
use test\verify\Runtime;

#[Runtime(extensions: ['bz2'])]
class Bzip2InputStreamTest extends DecompressingInputStreamTest {

  /** Create fixture */
  protected function fixture(InputStream $wrapped): InputStream { return new Bzip2InputStream($wrapped); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return bzcompress($in, $level); }

  /** Erroneous data */
  protected function erroneous() {

    // PHP 7.4RC1 is the first version to handle reading errors correctly, see
    // https://github.com/php/php-src/commit/d59aac58b3e7da7ad01a194fe9840d89725ea229
    if (PHP_VERSION_ID >= 70400) {
      yield ["BZh61AY&SY\331<Plain data>"];
    }
  }
}