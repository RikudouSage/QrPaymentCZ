# QR code payment (CZ)

[![Tests](https://github.com/RikudouSage/QrPaymentCZ/workflows/Tests/badge.svg)](https://github.com/RikudouSage/QrPaymentCZ/actions?query=workflow%3ATests)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/f87673759eb147998d1e8550af2a8b24)](https://www.codacy.com/gh/RikudouSage/QrPaymentCZ/dashboard)
[![Coverage Status](https://img.shields.io/coveralls/github/RikudouSage/QrPaymentCZ/master.svg)](https://coveralls.io/github/RikudouSage/QrPaymentCZ?branch=master)
[![Download](https://img.shields.io/packagist/dt/rikudou/czqrpayment.svg)](https://packagist.org/packages/rikudou/czqrpayment)

A simple library to generate QR payment code for Czech Republic.
All methods are documented in source code.

> See also QR code payment generator for [Slovak](https://github.com/RikudouSage/QrPaymentSK)
or [European Union](https://github.com/RikudouSage/QrPaymentEU) accounts.

> Using Symfony? See the [QR Payment Bundle](https://github.com/RikudouSage/QrPaymentBundle).

## Installation

Via composer: `composer require rikudou/czqrpayment`

## Usage

You can create the QR payment for an object implementing `\Rikudou\Iban\Iban\IbanInterface` or from account number
and bank account (which is then converted to `\Rikudou\Iban\Iban\CzechIbanAdapter` object).

> See [rikudou/iban](https://github.com/RikudouSage/IBAN) for description of the interface and classes which let
> you for example validate the iban and/or the bank account number and bank code.

From IbanInterface implementing classes:

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Rikudou\Iban\Iban\IBAN;
use Rikudou\Iban\Iban\CzechIbanAdapter;

// initialized with IBAN directly
$payment = new QrPayment(new IBAN('CZ5530300000001325090010'));

// initialized from Czech account number and bank code
$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'));

// the IBAN classes don't use strict typing so you can also use implicit conversion like this
// beware of bank codes that start with zero, those need to always be supplied as a string (like 0300)
$payment = new QrPayment(new CzechIbanAdapter(1325090010, 3030));

```

From account number and bank code directly:

```php
<?php

use Rikudou\CzQrPayment\QrPayment;

$payment = QrPayment::fromAccountAndBankCode('1325090010', '3030');

// the class does not use strict typing so you can also use implicit conversion like this
// beware of bank codes that start with zero, those need to always be supplied as a string (like 0300)
$payment = QrPayment::fromAccountAndBankCode(1325090010, 3030);
```

### Setting payment details

There are two approaches to setting payment details. You can set them in an associative array or using the methods
provided in the class.

**Using associative array**

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'), [
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
  QrPaymentOptions::DUE_DATE => date("Y-m-d", strtotime("+14 days")),
  QrPaymentOptions::INSTANT_PAYMENT => true,
]);
```

**Using methods**

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'));
$payment
    ->setVariableSymbol(123456)
    ->setAmount(100)
    ->setCurrency("CZK")
    ->setDueDate(new DateTimeImmutable('+14 days'))
    ->setInstantPayment(true);
```

## Exceptions

Description of exceptions thrown:

- `__construct()`
    - `InvalidArgumentException` - if you provide options in constructor and any of those options does not exist
    - `\Rikudou\CzQrPayment\Exception\InvalidValueException` - if any of the options contains an asterisk
    - `TypeError` - if any of the values is not assignable to the properties after standard php conversions
- `setOptions()` - same as constructor
- `getQrString()`
    - `\Rikudou\CzQrPayment\Exception\InvalidValueException` - if any of the options contains an asterisk or if the
    iban is not valid
- `getQrImage()`
    - `\Rikudou\CzQrPayment\Exception\MissingLibraryException` - if the endroid/qr-code library is missing

All of these exceptions (except `InvalidArgumentException` and `TypeError`) extend the base 
`\Rikudou\CzQrPayment\Exception\QrPaymentException`.

## QR Code image

This library provides many implementations of QR code image using its sister library
[rikudou/qr-payment-qr-code-provider](https://github.com/RikudouSage/QrPaymentQrCodeProvider). If any supported
QR code generating library is installed, the method `getQrCode()` will return an instance of 
`\Rikudou\QrPaymentQrCodeProvider\QrCode` which can be used to get an image containing the generated QR payment data.

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Endroid\QrCode\QrCode;

$payment = new QrPayment(...);

$qrCode = $payment->getQrCode();

// get the raw image data and display them in the browser
header('Content-Type: image/png');
echo $qrCode->getRawString();

// use in an img html tag
echo "<img src='{$qrCode->getDataUri()}'>";

// write to a file
$qrCode->writeToFile('/tmp/some-file.png');

// get the raw object from the underlying system
$raw = $qrCode->getRawObject();
// let's assume we're using endroid/qr-code v4
assert($raw instanceof QrCode);
// do some custom transformations
$raw->setLabelFontSize(15);
// the object is still referenced by the adapter, meaning we can now render it the same way as before
echo "<img src='{$qrCode->getDataUri()}'>";
```

## List of public methods

### Constructor

**Params**

- `\Rikudou\Iban\Iban\IbanInterface $iban` **required** - the IBAN for the payment
- `array|null $options` - the array with options.  The helper class `QrPaymentOptions` can be used for options names.

**Example**

```php
<?php
use Rikudou\CzQrPayment\QrPayment;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'));

// or with options

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'), [
  QrPaymentOptions::AMOUNT => 100
]);
```

### setOptions()

Sets the options, useful if you don't want to set them in constructor.

**Params**

- `array $options` **required** - the same as the constructor param `$options`

**Returns**

Returns itself, you can use this method for chaining.

**Example**

```php
<?php
use Rikudou\CzQrPayment\QrPayment;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'));

$payment->setOptions([
  QrPaymentOptions::AMOUNT => 100
]);
```

### getIban()

Returns the iban.

**Returns**

`\Rikudou\Iban\Iban\IbanInterface`

**Example**

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'));
$myIBAN = $payment->getIban(); 
```

### getQrString()

Returns the string that should be encoded in QR image.

**Returns**

`string`

**Example**

```php
<?php
use Rikudou\CzQrPayment\QrPayment;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;
use Rikudou\Iban\Iban\CzechIbanAdapter;

$payment = new QrPayment(new CzechIbanAdapter('1325090010', '3030'), [
  QrPaymentOptions::AMOUNT => 100,
  QrPaymentOptions::VARIABLE_SYMBOL => 1502,
  QrPaymentOptions::DUE_DATE => new DateTimeImmutable('+14 days'),
]);

$qrString = $payment->getQrString(); // SPD*1.0*ACC:CZ5530300000001325090010*AM:100.00*CC:CZK*X-PER:7*X-VS:1502*DT:20210413
```

### static fromAccountAndBankCode()

Returns a new instance of the payment object created from the account and bank code.
Is pretty much an alias to `new QrPayment(new CzechIbanAdapter())`.

**Params**

- `string $accountNumber` **required** - The account number
- `string $bankCode` **required** - The bank code

**Returns**

Returns itself, you can use this method for chaining.

**Example**

```php
<?php
use Rikudou\CzQrPayment\QrPayment;

$payment = QrPayment::fromAccountAndBankCode('1325090010', '3030');
// do all the other stuff
```

### getQrImage()

Returns a Qr code via suggested third-party library.

**Returns**

`\Endroid\QrCode\QrCode`

**Example**

```php
<?php

use Rikudou\CzQrPayment\QrPayment;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;

$payment = QrPayment::fromAccountAndBankCode('1325090010', '3030')->setOptions([
  QrPaymentOptions::AMOUNT => 100
]);

header('Content-Type: image/png');
echo $payment->getQrImage()->writeString();

```

### Options

This is a list of options you can set.

- `int $variableSymbol` - the variable symbol, has no default
- `int $specificSymbol` - the specific symbol, has no default
- `int $constantSymbol` - the constant symbol, has no default
- `string $currency` - [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217#Active_codes) code for currency, 
  defaults to `CZK`
- `string $comment` - the payment comment, has no default
- `int $repeat` - the number of days that the payment should be repeated if it fails, defaults to `7`
- `string $internalId` - internal id of the payment, has no default
- `DateTimeInterface dueDate` - the due date for payment, has no default
- `float $amount` - the amount for the payment, shouldn't have more than 2 decimal places, has no default
- `string $country` - [ISO 3166-1 alpha-2 code](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2#Officially_assigned_code_elements)
  for country, defaults to `CZ`
- `bool $instantPayment` - whether the payment should be made as instant instead of standard payment
  (depends on bank support)

All of these options can be set using the `QrPaymentOptions` helper class as constants for the constructor or
`setOptions()` or as methods.

For example, the `amount` can be set in an array using the constant `QrPaymentOptions::AMOUNT` or using the
method `setAmount()`.