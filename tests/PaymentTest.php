<?php

namespace rikudou\CzQrPayment\Tests;

use PHPUnit\Framework\TestCase;
use rikudou\CzQrPayment\QrPayment;

class PaymentTest extends TestCase
{

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

    private function getInstance(): QrPayment
    {
        return new QrPayment(1325090010, 3030);
    }

}