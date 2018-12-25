<?php

namespace rikudou\CzQrPayment\Tests;

use PHPUnit\Framework\TestCase;
use rikudou\CzQrPayment\QrPayment;
use rikudou\CzQrPayment\QrPaymentException;
use rikudou\CzQrPayment\QrPaymentOptions;

class PaymentTest extends TestCase
{

    private $autoloaders = null;

    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->autoloaders = spl_autoload_functions();
    }

    public function testAccountOnly()
    {
        $payment = $this->getInstance();
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7", $payment->getQrString());
    }

    public function testVariableSymbol()
    {
        $payment = $this->getInstance()->setVariableSymbol(123456);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*X-VS:123456", $payment->getQrString());
    }

    public function testSpecificSymbol()
    {
        $payment = $this->getInstance()->setSpecificSymbol(123456);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*X-SS:123456", $payment->getQrString());
    }

    public function testConstantSymbol()
    {
        $payment = $this->getInstance()->setConstantSymbol(123456);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*X-KS:123456", $payment->getQrString());
    }

    public function testCurrency()
    {
        $payment = $this->getInstance()->setCurrency("EUR");
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:EUR*X-PER:7", $payment->getQrString());
    }

    public function testComment()
    {
        $payment = $this->getInstance()->setComment("RANDOM COMMENT");
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*MSG:RANDOM COMMENT", $payment->getQrString());
    }

    public function testRepeat()
    {
        $payment = $this->getInstance()->setRepeat(5);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:5", $payment->getQrString());
    }

    public function testInternalId()
    {
        $payment = $this->getInstance()->setInternalId("ABC123");
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*X-ID:ABC123", $payment->getQrString());
    }

    public function testDueDate()
    {
        $payment = $this->getInstance()->setDueDate(new \DateTime("2018-12-24"));
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7*DT:20181224", $payment->getQrString());
    }

    public function testAmount()
    {
        $payment = $this->getInstance()->setAmount(100);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:100.00*CC:CZK*X-PER:7", $payment->getQrString());
        $payment->setAmount(50.5);
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:50.50*CC:CZK*X-PER:7", $payment->getQrString());
    }

    public function testCountry()
    {
        $payment = $this->getInstance()->setCountry("SK");
        $this->assertEquals("SPD*1.0*ACC:SK5330300000001325090010*AM:0.00*CC:CZK*X-PER:7", $payment->getQrString());
    }

    public function testFromIban()
    {
        $payment = QrPayment::fromIBAN("CZ5530300000001325090010");
        $this->assertEquals("SPD*1.0*ACC:CZ5530300000001325090010*AM:0.00*CC:CZK*X-PER:7", $payment->getQrString());
    }


    public function testConstructorOptions()
    {
        $payment = new QrPayment(1325090010, 3030, [
            QrPaymentOptions::VARIABLE_SYMBOL => 123456,
            QrPaymentOptions::SPECIFIC_SYMBOL => 123456,
            QrPaymentOptions::CONSTANT_SYMBOL => 1234,
            QrPaymentOptions::CURRENCY => "EUR",
            QrPaymentOptions::COMMENT => "random comment",
            QrPaymentOptions::REPEAT => 5,
            QrPaymentOptions::INTERNAL_ID => "ID123",
            QrPaymentOptions::DUE_DATE => new \DateTime("2018-12-24"),
            QrPaymentOptions::AMOUNT => 100,
            QrPaymentOptions::COUNTRY => "DE"
        ]);

        $this->assertEquals(
            "SPD*1.0*ACC:DE1230300000001325090010*AM:100.00*CC:EUR*X-PER:5*MSG:random comment*X-ID:ID123*X-VS:123456*X-SS:123456*X-KS:1234*DT:20181224",
            $payment->getQrString()
        );
    }

    public function testInvalidDateObject()
    {
        $this->expectException(QrPaymentException::class);
        $payment = $this->getInstance();

        $payment->setDueDate(new \stdClass());
        $payment->getQrString();
    }

    public function testInvalidDateString()
    {
        $this->expectException(QrPaymentException::class);
        $payment = $this->getInstance();

        $payment->setDueDate("24. 12. 2018");
        $payment->getQrString();
    }

    public function testGetQrImageFailure()
    {
        $this->expectException(get_class(new QrPaymentException())); // autoload the class
        $payment = $this->getInstance();

        $this->unregisterAutoloader();
        try {
            $payment->getQrImage();
        } catch (\Throwable $exception) {
            $this->reregisterAutoloader();
            throw $exception;
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testGetQrImage()
    {
        $this->reregisterAutoloader();

        $payment = $this->getInstance()->setCurrency("EUR")->setAmount(153)->setComment("TesT");
        $this->assertEquals($payment->getQrString(), $payment->getQrImage()->getText());

        if(function_exists("xdebug_get_headers")) {
            $payment->getQrImage(true);
            $this->assertContains("Content-type: image/png", xdebug_get_headers());
        }
    }

    private function getInstance(): QrPayment
    {
        return new QrPayment(1325090010, 3030);
    }

    private function unregisterAutoloader()
    {
        foreach ($this->autoloaders as $autoloader) {
            spl_autoload_unregister($autoloader);
        }
    }

    private function reregisterAutoloader()
    {
        foreach ($this->autoloaders as $autoloader) {
            spl_autoload_register($autoloader);
        }
    }

}