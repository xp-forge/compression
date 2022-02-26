<?php namespace io\streams\compress\unittest;

use io\streams\compress\{Algorithm, Algorithms};
use io\streams\{Compression, InputStream, OutputStream};
use unittest\{Assert, Before, Test, Values};

class AlgorithmsTest {
  private $supported, $unsupported;

  #[Before]
  public function algorithm() {
    $this->supported= new class() implements Algorithm {
      public function supported(): bool { return true; }
      public function name(): string { return 'test'; }
      public function token(): string { return 'x-test'; }
      public function extension(): string { return '.test'; }
      public function level(int $select): int { return $select; }
      public function open(InputStream $in): InputStream { return $in; }
      public function create(OutputStream $out, int $method= Compression::DEFAULT): OutputStream { return $out; }
    };
    $this->unsupported= new class() implements Algorithm {
      public function supported(): bool { return false; }
      public function name(): string { return 'lzw'; }
      public function token(): string { return 'compress'; }
      public function extension(): string { return '.lz'; }
      public function level(int $select): int { return $select; }
      public function open(InputStream $in): InputStream { return $in; }
      public function create(OutputStream $out, int $method= Compression::DEFAULT): OutputStream { return $out; }
    };
  }

  #[Test]
  public function can_create() {
    new Algorithms();
  }

  #[Test]
  public function add_returns_instance() {
    $fixture= new Algorithms();
    Assert::equals($fixture, $fixture->add($this->supported));
  }

  #[Test]
  public function find_on_empty() {
    Assert::null((new Algorithms())->find('test'));
  }

  #[Test, Values(['test', 'x-test', '.test'])]
  public function find_by($lookup) {
    Assert::equals($this->supported, (new Algorithms())->add($this->supported)->find($lookup));
  }

  #[Test]
  public function find_non_existant() {
    Assert::null((new Algorithms())->add($this->supported)->find('non-existant'));
  }

  #[Test]
  public function iterate() {
    Assert::equals(
      ['test' => $this->supported, 'lzw' => $this->unsupported],
      iterator_to_array((new Algorithms())->add($this->supported, $this->unsupported))
    );
  }

  #[Test]
  public function supported() {
    Assert::equals(
      ['test'=> $this->supported],
      iterator_to_array((new Algorithms())->add($this->supported, $this->unsupported)->supported())
    );
  }

  #[Test]
  public function remove_existant() {
    Assert::true((new Algorithms())->add($this->supported)->remove($this->supported));
  }

  #[Test]
  public function remove_non_existant() {
    Assert::false((new Algorithms())->add($this->supported)->remove($this->unsupported));
  }

  #[Test]
  public function remove_on_empty() {
    Assert::false((new Algorithms())->remove($this->supported));
  }

  #[Test, Values(['test', 'x-test', '.test'])]
  public function find_after_removing($lookup) {
    $fixture= (new Algorithms())->add($this->supported);
    $fixture->remove($this->supported);

    Assert::null($fixture->find($lookup));
  }
}