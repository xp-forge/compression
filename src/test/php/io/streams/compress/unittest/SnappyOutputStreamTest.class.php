<?php namespace io\streams\compress\unittest;

use io\streams\compress\SnappyOutputStream;
use io\streams\{Streams, MemoryOutputStream};
use test\{Assert, Test, Values};
use util\Bytes;

class SnappyOutputStreamTest {

  /** Creates a fixture */
  private function fixture($out, $length) {
    return new SnappyOutputStream($out, $length);
  }

  #[Test]
  public function can_create() {
    $this->fixture(new MemoryOutputStream(), 0);
  }

  #[Test, Values([[0, "\000"], [5, "\005"], [255, "\377\001"], [256, "\200\002"], [65536, "\200\200\004"]])]
  public function length_as_varint($length, $expected) {
    $out= new MemoryOutputStream();
    $this->fixture($out, $length);

    Assert::equals(new Bytes($expected), new Bytes($out->bytes()));
  }

  #[Test]
  public function literal() {
    $out= new MemoryOutputStream();
    $compress= $this->fixture($out, 5);
    $compress->write('Hello');
    $compress->close();

    Assert::equals(new Bytes("\005\020Hello"), new Bytes($out->bytes()));
  }

  #[Test]
  public function copy() {
    $out= new MemoryOutputStream();
    $compress= $this->fixture($out, 23);
    $compress->write("Hello\n=================");
    $compress->close();

    Assert::equals(new Bytes("\027\030Hello\n=\076\001\000"), new Bytes($out->bytes()));
  }

  #[Test]
  public function repeated_input_compressed() {
    $out= new MemoryOutputStream();
    $compress= $this->fixture($out, 20);
    $compress->write('Hello');
    $compress->write('Hello');
    $compress->write('Hello');
    $compress->write('Hello');
    $compress->close();

    Assert::equals(new Bytes("\024\020Hello:\005\000"), new Bytes($out->bytes()));
  }
}