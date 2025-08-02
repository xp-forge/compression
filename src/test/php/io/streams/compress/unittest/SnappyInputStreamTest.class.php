<?php namespace io\streams\compress\unittest;

use io\IOException;
use io\streams\compress\SnappyInputStream;
use io\streams\{Streams, MemoryInputStream};
use test\{Assert, Expect, Test};

class SnappyInputStreamTest {

  /** Creates a fixture */
  private function fixture($bytes) {
    return new SnappyInputStream(new MemoryInputStream($bytes));
  }

  #[Test]
  public function can_create() {
    $this->fixture("\x00");
  }

  #[Test]
  public function literal() {
    Assert::equals('Hello', Streams::readAll($this->fixture("\005\020Hello")));
  }

  #[Test]
  public function copy() {
    Assert::equals(
      "Hello\n=================",
      Streams::readAll($this->fixture("\026\030Hello\n=\076\001\000"))
    );
  }

  #[Test, Expect(IOException::class)]
  public function from_empty() {
    Streams::readAll($this->fixture(''));
  }

  #[Test, Expect(IOException::class)]
  public function not_enough_input() {
    Streams::readAll($this->fixture("\x01"));
  }
}