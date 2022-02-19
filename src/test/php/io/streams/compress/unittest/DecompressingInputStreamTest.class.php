<?php namespace io\streams\compress\unittest;

use io\IOException;
use io\streams\{InputStream, MemoryInputStream};
use unittest\{Assert, Before, PrerequisitesNotMetError, Test};
use util\Bytes;

abstract class DecompressingInputStreamTest {

  /** Get filter we depend on */
  protected abstract function filter(): string;

  /** Create fixture */
  protected abstract function fixture(InputStream $wrapped): InputStream;

  /** Compress data */
  protected abstract function compress(string $in, int $level): string;

  #[Before]
  public function verifyExtensionLoaded() {
    $depend= $this->filter();
    if (!in_array($depend, stream_get_filters())) {
      throw new PrerequisitesNotMetError(ucfirst($depend).' stream filter not available', null, [$depend]);
    }
  }

  #[Test]
  public function empty_read() {
    $in= new MemoryInputStream($this->compress('', 6));
    $fixture= $this->fixture($in);
    $chunk= $fixture->read();
    $fixture->close();

    Assert::equals('', $chunk);
  }

  #[Test]
  public function single_read() {
    $in= new MemoryInputStream($this->compress('Hello', 6));
    $fixture= $this->fixture($in);
    $chunk= $fixture->read();
    $fixture->close();

    Assert::equals('Hello', $chunk);
  }

  #[Test]
  public function multiple_reads() {
    $in= new MemoryInputStream($this->compress('Hello World', 6));
    $fixture= $this->fixture($in);
    $chunks= [
      $fixture->read(5),
      $fixture->read(1),
      $fixture->read(5),
    ];
    $fixture->close();

    Assert::equals(['Hello', ' ', 'World'], $chunks);
  }

  #[Test, Values([1, 2, 6, 9])]
  public function at_level($level) {
    $in= new MemoryInputStream($this->compress('Hello', $level));
    $fixture= $this->fixture($in);
    $chunk= $fixture->read();
    $fixture->close();

    Assert::equals('Hello', $chunk);
  }

  #[Test]
  public function closing_right_after_creation() {
    $fixture= $this->fixture(new MemoryInputStream($this->compress('Hello', 1)));
    $fixture->close();
  }

  #[Test]
  public function closing_twice_has_no_effect() {
    $fixture= $this->fixture(new MemoryInputStream($this->compress('Hello', 1)));
    $fixture->close();
    $fixture->close();
  }
}