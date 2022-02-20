Compression streams
===================

[![Build status on GitHub](https://github.com/xp-forge/compression/workflows/Tests/badge.svg)](https://github.com/xp-forge/compression/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/compression/version.png)](https://packagist.org/packages/xp-forge/compression)

Compressing output and decompressing input streams including GZip, BZip2 and Brotli.

Examples
--------
Reading a GZIP-compressed file:

```php
use io\streams\FileInputStream;
use io\streams\compress\GzipInputStream;

$in= new GzipInputStream(new FileInputStream('message.txt.gz'));
while ($in->available()) {
  echo $in->read();
}
$in->close();
```

Writing a file, compressing the data on-the-fly with BZIP2:

```php
use io\streams\FileOutputStream;
use io\streams\compress\Bzip2OutputStream;

$out= new Bzip2OutputStream(new FileOutputStream('message.txt.bz2'));
$out->write('Hello World!');
$out->write("\n");
$out->close();
```

Fetching a given URL using [HTTP Accept-Encoding and Content-Encoding](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding):

```php
use io\streams\Compression;
use peer\http\HttpConnection;

// Compile list of supported compression algorithms, e.g. "gzip, br"
$supported= [];
foreach (Compression::algorithms()->supported() as $compression) {
  $supported[]= $compression->token();
}
echo "== Sending ", implode(', ', $supported), " ==\n";

// Make request, sending supported content encodings via Accept-Encoding
$conn= new HttpConnection($argv[1]);
$res= $conn->get(null, ['Accept-Encoding' => implode(', ', $supported)]);

// Handle Content-Encoding header
if ($encoding= $res->header('Content-Encoding')) {
  $compression= Compression::named($encoding[0]);

  echo "== Using ", $compression->name(), " ==\n";
  $in= $compression->open($res->in());
} else {
  echo "== Uncompressed ==\n";
  $in= $res->in();
}

// Write contents to output
while ($in->available()) {
  echo $in->read();
}
$in->close();
```

Dependencies
------------

* **GZIP** - requires PHP's ["zlib" extension](https://www.php.net/zlib)
* **BZIP2** - requires PHP's ["bzip2" extension](https://www.php.net/bzip2)
* **Brotli** - requires https://github.com/kjdev/php-ext-brotli