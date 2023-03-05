<?php namespace io\streams\compress\unittest;

use io\IOException;
use io\streams\compress\GzipInputStream;
use io\streams\{InputStream, MemoryInputStream};
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};

#[Runtime(extensions: ['zlib'])]
class GzipInputStreamTest extends DecompressingInputStreamTest {

  /** Create fixture */
  protected function fixture(InputStream $wrapped): InputStream { return new GzipInputStream($wrapped); }

  /** Compress data */
  protected function compress(string $in, int $level): string { return gzencode($in, $level); }

  /** Return erroneous data */
  protected function erroneous() {
    yield ["\037\213\b\000\000\000\000\000\000\n<Plain data>"];
  }

  /** @return iterable */
  private function dataWithFileName() {
    yield ["\x1F\x8B\x08\x08\x82\x86\xE0T\x00\x03test.txt\x00\xF3H\xCD\xC9\xC9\x07\x00\x82\x89\xD1\xF7\x05\x00\x00\x00"];
  }

  #[Test, Values(from: 'dataWithFileName')]
  public function data_with_original_filename($data) {
    $fixture= $this->fixture(new MemoryInputStream($data));
    $chunk= $fixture->read();
    $fixture->close();
    Assert::equals('Hello', $chunk);
  }

  #[Test, Values(from: 'dataWithFileName')]
  public function header_with_original_filename($data) {
    $fixture= $this->fixture(new MemoryInputStream($data));
    $fixture->close();
    Assert::equals('test.txt', $fixture->header()['filename']);
  }

  #[Test, Expect(IOException::class)]
  public function from_empty() {
    $this->fixture(new MemoryInputStream(''));
  }
}