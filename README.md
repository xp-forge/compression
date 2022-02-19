Compression streams
===================

[![Build status on GitHub](https://github.com/xp-forge/compression/workflows/Tests/badge.svg)](https://github.com/xp-forge/compression/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/compression/version.png)](https://packagist.org/packages/xp-forge/compression)

Compressing output and decompressing input streams.

Example
-------
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

Supported compression formats:

* **GZIP** - using PHP's ["zlib" extension](https://www.php.net/zlib)
* **BZIP2** - using PHP's ["bzip2" extension](https://www.php.net/bzip2)
* **Brotli** - using https://github.com/kjdev/php-ext-brotli