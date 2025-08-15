<?php namespace io\streams\compress\unittest;

use io\streams\MemoryInputStream;
use io\streams\compress\{BufferedInputStream, None};
use lang\IllegalArgumentException;
use test\{Assert, Expect, Test, Values};

class BufferedInputStreamTest {

  #[Test]
  public function can_create_with_algorithm() {
    new BufferedInputStream(new MemoryInputStream(''), new None());
  }

  #[Test]
  public function can_create_with_function() {
    new BufferedInputStream(new MemoryInputStream(''), fn($data) => $data);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function illegal_compress() {
    new BufferedInputStream(new MemoryInputStream(''), null);
  }

  #[Test, Values([1, 8192, 8193, 65536])]
  public function read_completely($repeat) {
    $in= new BufferedInputStream(new MemoryInputStream($repeat), fn($data) => str_repeat('*', (int)$data));

    $decompressed= '';
    while ($in->available()) {
      $decompressed.= $in->read();
    }

    Assert::equals($repeat, strlen($decompressed));
  }
}