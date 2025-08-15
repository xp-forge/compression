<?php namespace io\streams\compress\unittest;

use io\streams\compress\{BufferedOutputStream, None};
use io\streams\{OutputStream, MemoryOutputStream};
use lang\IllegalArgumentException;
use test\{Assert, Expect, Test};

class BufferedOutputStreamTest {

  #[Test]
  public function can_create_with_algorithm() {
    new BufferedOutputStream(new MemoryOutputStream(), new None());
  }

  #[Test]
  public function can_create_with_function() {
    new BufferedOutputStream(new MemoryOutputStream(), fn($data) => $data);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function illegal_compress() {
    new BufferedOutputStream(new MemoryOutputStream(), null);
  }

  #[Test]
  public function writes_on_close() {
    $out= new MemoryOutputStream();

    $compress= new BufferedOutputStream($out, fn($data) => 'Z:'.strlen($data));
    $compress->write('Test');
    $compress->write('ed');
    $compress->close();

    Assert::equals('Z:6', $out->bytes());
  }

  #[Test]
  public function closes_underlying_stream() {
    $out= new class() implements OutputStream {
      public $closed= false;
      public function write($bytes) { }
      public function flush() { }
      public function close() { $this->closed= true; }
    };

    $compress= new BufferedOutputStream($out, new None());
    $closed= $out->closed;
    $compress->close();

    Assert::equals([false, true], [$closed, $out->closed]);
  }
}