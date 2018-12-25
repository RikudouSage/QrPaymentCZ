# QR code payment (CZ)

![master build status](https://travis-ci.com/RikudouSage/QrPaymentCZ.svg?branch=master "master branch build status")
[![Coverage Status](https://img.shields.io/coveralls/github/RikudouSage/QrPaymentCZ/master.svg)](https://coveralls.io/github/RikudouSage/QrPaymentCZ?branch=master)
[![Download](https://img.shields.io/packagist/dt/rikudou/czqrpayment.svg)](https://packagist.org/packages/rikudou/czqrpayment)

A simple library to generate QR payment code for Czech Republic.
All methods are documented in source code.

> See also QR code payment generator for [Slovak](https://github.com/RikudouSage/QrPaymentSK)
or [European Union](https://github.com/RikudouSage/QrPaymentEU) accounts.

> Using Symfony? See the [QR Payment Bundle](https://github.com/RikudouSage/QrPaymentBundle).

## Installation

Via composer: `composer require rikudou/czqrpayment`

Manually: clone the repository and include the `QrPaymentException.php`,
`QrPaymentOptions.php` and `QrPayment.php` in your project.

## Usage

You can create the Qr payment from account number and bank code or from IBAN.

From account number and bank code:

```php
<?php

use rikudou\CzQrPayment\QrPayment;

$payment = new QrPayment(1325090010, 3030);

```

From IBAN:

```php
<?php

use rikudou\CzQrPayment\QrPayment;

$payment = QrPayment::fromIBAN("CZ5530300000001325090010");
```

### Setting payment details

There are two approaches to setting payment details. You can set them in associative array or using the methods
provided in the class.

**Using associative array**

```php
<?php

use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentOptions;

$payment = new QrPayment(1325090010, 3030, [
  QrPaymentOptions::VARIABLE_SYMBOL => 123456,
  QrPaymentOptions::AMOUNT => 100,
  QrPaymentOptions::CURRENCY => "CZK",
  QrPaymentOptions::DUE_DATE => date("Y-m-d", strtotime("+14 days"))
]);

// or you can assign the options later via setOptions()

$payment->setOptions([
  QrPaymentOptions::VARIABLE_SYMBOL => 123456,
  QrPaymentOptions::AMOUNT => 100,
  QrPaymentOptions::CURRENCY => "CZK",
  QrPaymentOptions::DUE_DATE => date("Y-m-d", strtotime("+14 days"))
]);
```

**Using methods**

```php
<?php
use rikudou\CzQrPayment\QrPayment;

$payment = new QrPayment(1325090010, 3030);
$payment->setVariableSymbol(123456)->setAmount(100)->setCurrency("CZK")->setDueDate(date("Y-m-d", strtotime("+14 days")));
```

## Exceptions

The only exception thrown by this library is `rikudou\CzQrPayment\QrPaymentException`.

**Methods that can throw exception:**

- `__construct()` - if you supply options array and any of the values contains asterisk (`*`)
- `setOptions()` - if any of the values contains asterisk (`*`)
- `getIBAN()` - if any property contains asterisk(`*`)
- `getQrString()` - if any property contains asterisk(`*`) or if the date is not a valid date
- `getQrImage()` - if any property contains asterisk(`*`) or if the date is not a valid date
or if the `endroid\qrcode` is not loaded

**Error codes**

The `QrPaymentException` contains constants to help you debugging the reason for the exception throw.

- `QrPaymentException::ERR_ASTERISK` - this code is thrown when any of the properties contains asterisk (`*`)
- `QrPaymentException::ERR_DATE` - this code is thrown if the date is not a valid date
- `QrPaymentException::ERR_MISSING_LIBRARY` - this code is thrown if you try to use `getQrImage()` method but don't have
the `endroid\qrcode` library installed


## List of public methods

### Constructor

**Params**

- `int|string $account` - the account number
- `int|string $bank` - the bank code
- `array $options` - the array with options (not required).
The helper class `QrPaymentOptions` can be used for options names.

**Example**

```php
<?php
use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentOptions;

$payment = new QrPayment(1325090010, 3030);

// or with options

$payment = new QrPayment(1325090010, 3030, [
  QrPaymentOptions::AMOUNT => 100
]);
```

### setOptions()

Sets the options, useful if you don't want to set them in constructor.

**Params**

- `array $options` - the same as the constructor param `$options`

**Returns**

Returns itself, you can use this method for chaining.

**Example**

```php
<?php
use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentOptions;

$payment = new QrPayment(1325090010, 3030);

$payment->setOptions([
  QrPaymentOptions::AMOUNT => 100
]);
```

### getIBAN()

Returns the IBAN, either from supplied IBAN or generated from account number and 
bank code.


**Returns**

`string`

**Example**

```php
<?php

use rikudou\CzQrPayment\QrPayment;

$payment = new QrPayment(1325090010, 3030);
$myIBAN = $payment->getIBAN(); 
// $myIBAN now holds CZ5530300000001325090010
```

### getQrString()

Returns the string that should be encoded in QR image.

**Returns**

`string`

**Example**

```php
<?php
use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentOptions;

$payment = new QrPayment(1325090010, 3030, [
  QrPaymentOptions::AMOUNT => 100,
  QrPaymentOptions::VARIABLE_SYMBOL => 1502,
  QrPaymentOptions::DUE_DATE => date("Y-m-d", strtotime("+14 days"))
]);

$qrString = $payment->getQrString(); // SPD*1.0*ACC:CZ5530300000001325090010*AM:100.00*CC:CZK*X-PER:7*X-VS:1502*DT:20170928
```

### static fromIBAN()

Returns new instance of the payment object created from IBAN.

**Params**

- `string $iban` - The IBAN of the account

**Returns**

Returns itself, you can use this method for chaining.

**Example**

```php
<?php
use rikudou\CzQrPayment\QrPayment;

$payment = QrPayment::fromIBAN("CZ5530300000001325090010");
// do all the other stuff
```

### getQrImage()

Returns a Qr code via third-party library.

**Params**

- `bool $setPngHeader` - if true, this method calls `header()` function to set
content type to image/png, defaults to false

**Returns**

`\Endroid\QrCode\QrCode`

**Example**

```php
<?php

use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentOptions;

$payment = QrPayment::fromIBAN("CZ5530300000001325090010")->setOptions([
  QrPaymentOptions::AMOUNT => 100
]);

$payment->getQrImage(true) // sets the content-type and renders
    ->writeString();

```

### Options

This is a list of options you can set.

- `int variableSymbol` - the variable symbol, has no default
- `int specificSymbol` - the specific symbol, has no default
- `int constantSymbol` - the constant symbol, has no default
- `string currency` - three letter code for currency, defaults to `CZK`
- `string comment` - the payment comment, has no default
- `int repeat` - the count of days that the payment should be repeated if it fails,
defaults to `7`
- `string internalId` - internal id of the payment, has no default
- `string|DateTime dueDate` - the due date for payment, should be an instance of
`DateTime` class or a string that can be parsed by `strtotime()`, has no default
- `float amount` - the amount for the payment, can't have more than 2 decimal places,
has no default
- `country` - two letter code for country, defaults to `CZ`

All of these options can be set using the `QrPaymentOptions` helper class as constants
for constructor or `setOptions()` or as methods.

For example, the `amount` can be set in array using the constant
`QrPaymentOptions::AMOUNT` or using the method `setAmount()`.