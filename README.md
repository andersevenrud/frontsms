# Front SMS API PHP Library

Provides a simple way to use Front SMS in PHP.

## Installation

```
composer require andersevenrud/frontsms
```

## Usage

```

use FrontSMS\FrontSMS;


$client = new FrontSMS([
  'serviceid' => 'foo',
  'fromid' => 'Someone'
]);

$client->send(12345678, 'hello world')!

```

## Changelog

* **0.5.1** - Updated composer.json
* **0.5.0** - Initial release

## License

MIT
