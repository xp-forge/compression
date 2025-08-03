<?php namespace io\streams\compress\unittest;

use io\IOException;
use io\streams\compress\SnappyInputStream;
use io\streams\{Streams, MemoryInputStream};
use test\{Assert, Expect, Test, Values};

class SnappyInputStreamTest {

  /** Creates a fixture */
  private function fixture($bytes) {
    return new SnappyInputStream(new MemoryInputStream($bytes));
  }

  #[Test]
  public function can_create() {
    $this->fixture("\x00");
  }

  #[Test, Values([[5, "\005\020"], [255, "\377\001\360\376"], [256, "\200\002\364\377\000"]])]
  public function literals($length, $encoded) {
    $payload= str_repeat('*', $length);
    Assert::equals($payload, Streams::readAll($this->fixture($encoded.$payload)));
  }

  #[Test]
  public function copy() {
    Assert::equals(
      "Hello\n=================",
      Streams::readAll($this->fixture("\027\030Hello\n=\076\001\000"))
    );
  }

  #[Test, Expect(class: IOException::class, message: 'Not enough input, expected 1')]
  public function from_empty() {
    Streams::readAll($this->fixture(''));
  }

  #[Test, Expect(class: IOException::class, message: 'Not enough input, expected 1')]
  public function not_enough_input() {
    Streams::readAll($this->fixture("\x01"));
  }
}