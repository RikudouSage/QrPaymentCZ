# QR code payment (CZ)

A simple library to generate QR payment code for Czech Republic.
All methods are documented in source code.

## Usage

```php
<?php
$qr = new QrPayment(1325090010, 3030, [ // new QrPayment(account_number, bank_code, options)
        "amount" => 500,
        "specific_symbol" => 111
]);
$qr->setOptions([
	"variable_symbol" => 123456,
	"constant_symbol" => 666
]);
```

Then you have two options: take the generated QR string by calling `$qr->getQrString()` and generate the QR code by whatever generator you want, or you can call `$qr->getQr()->render()` which uses endroid/qrcode from composer.

## List of public methods

- `__construct(int, int, [array])` - takes three parameters, first is the account number, second is the bank code and third parameter is options
- `setOptions(array)` - does the same as the third parameter in `__construct()` - actually the construct calls this function to assign properties. This function traverses array and checks whether property with given array key exists, if so, it assigns it. You can also assign these properties directly by calling e.g. `$this->amount = 500`.
    - list of properties (if bank does not understand the property, it ignores it):
        - `private $account` - is set in construct, it's the account number
        - `private $bank` - is set in construct, it's the bank code
        - `public $variable_symbol` - the variable symbol for payment
        - `public $specific_symbol` - the specific symbol for payment
        - `public $constant_symbol` - the constant symbol for payment
        - `public $currency` - the three letter currency code, defaults to CZK
        - `public $comment` - the message for payment, defaults to 'QR Payment'
        - `public $repeat` - number of days to repeat the payment if it fails. Defaults to 7. Not all banks understand this directive.
        - `public $internal_id` - you can set some sort of internal id, e.g. payment reference in e-shop system, etc. Not all banks understand this directive.
        - `public $due_date` - you can set the due date here. It must be able to be parsed by `strtotime()` function
        - `public $amount` - the amount for the payment.
- `accToIBAN()` - converts the account number and bank code to IBAN which is required for QR payment
- `getQrString()` - returns the generated QR payment string
- `getQr([bool])` - returns instance of `Endroid\QrCode\QrCode` object and sets the QR payment string as text.

## Dependencies
1. `bcmath` - math library to work with big numbers, you must have php compiled with support for it (http://php.net/manual/en/book.bc.php)
2. `endroid/qrcode` - php library for generating qr codes, you can get it from composer or https://github.com/endroid/QrCode
