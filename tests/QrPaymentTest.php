<?php

namespace Rikudou\CzQrPayment\Tests;

use DateTimeImmutable;
use DateTimeInterface;
use Endroid\QrCode\QrCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Rikudou\CzQrPayment\Exception\InvalidValueException;
use Rikudou\CzQrPayment\Exception\MissingLibraryException;
use Rikudou\CzQrPayment\Options\QrPaymentOptions;
use Rikudou\CzQrPayment\QrPayment;
use Rikudou\Iban\Iban\IBAN;
use Rikudou\Iban\Iban\IbanInterface;
use Rikudou\Iban\Validator\ValidatorInterface;

final class QrPaymentTest extends TestCase
{
    /**
     * @var QrPayment
     */
    private $instance;

    /**
     * @var array
     */
    private $autoloaders;

    protected function setUp(): void
    {
        $this->instance = new QrPayment($this->getIban());
        $this->autoloaders = spl_autoload_functions();
    }

    public function testConstructorOptions()
    {
        $instance = new QrPayment($this->getIban(), [
            QrPaymentOptions::VARIABLE_SYMBOL => 123,
            QrPaymentOptions::SPECIFIC_SYMBOL => 456,
            QrPaymentOptions::CONSTANT_SYMBOL => 789,
            QrPaymentOptions::CURRENCY => 'EUR',
            QrPaymentOptions::COMMENT => 'SOME TEXT',
            QrPaymentOptions::REPEAT => 3,
            QrPaymentOptions::INTERNAL_ID => '123456',
            QrPaymentOptions::DUE_DATE => new DateTimeImmutable('2021-01-31'),
            QrPaymentOptions::AMOUNT => 500,
            QrPaymentOptions::PAYEE_NAME => 'Random Dude',
        ]);

        self::assertEquals(
            'SPD*1.0*ACC:CZ5530300000001325090010*AM:500.00*CC:EUR*X-PER:3*MSG:SOME TEXT*X-ID:123456*X-VS:123*X-SS:456*X-KS:789*RN:Random Dude*DT:20210131',
            $instance->getQrString()
        );

        $this->expectException(InvalidValueException::class);
        new QrPayment($this->getIban(), [
            QrPaymentOptions::COMMENT => 'Something containing *',
        ]);
    }

    public function testSetOptions()
    {
        $this->instance->setOptions([
            QrPaymentOptions::VARIABLE_SYMBOL => 123,
            QrPaymentOptions::SPECIFIC_SYMBOL => 456,
            QrPaymentOptions::CONSTANT_SYMBOL => 789,
            QrPaymentOptions::CURRENCY => 'EUR',
            QrPaymentOptions::COMMENT => 'SOME TEXT',
            QrPaymentOptions::REPEAT => 3,
            QrPaymentOptions::INTERNAL_ID => '123456',
            QrPaymentOptions::DUE_DATE => new DateTimeImmutable('2021-01-31'),
            QrPaymentOptions::AMOUNT => 500,
            QrPaymentOptions::PAYEE_NAME => 'Random Dude',
        ]);
        self::assertEquals(
            'SPD*1.0*ACC:CZ5530300000001325090010*AM:500.00*CC:EUR*X-PER:3*MSG:SOME TEXT*X-ID:123456*X-VS:123*X-SS:456*X-KS:789*RN:Random Dude*DT:20210131',
            $this->instance->getQrString()
        );

        $this->expectException(InvalidValueException::class);
        $this->instance->setOptions([
            QrPaymentOptions::COMMENT => 'Something containing *',
        ]);
    }

    public function testSetOptionsInvalidOption()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->instance->setOptions([
            'test' => 'test',
        ]);
    }

    public function testGetQrImageFailure()
    {
        try {
            $this->expectException(get_class(new MissingLibraryException()));
            $this->unregisterAutoloader();
            $this->instance->getQrImage();
        } finally {
            $this->reregisterAutoloader();
        }
    }

    public function testGetQrImage()
    {
        $image = $this->instance->getQrImage();
        self::assertInstanceOf(QrCode::class, $image);
        self::assertEquals($this->instance->getQrString(), $image->getText());
    }

    public function testFromAccountAndBankCode()
    {
        $instance = QrPayment::fromAccountAndBankCode(1325090010, 3030);
        self::assertEquals($this->getDefaultEmptyString(), $instance->getQrString());

        $this->expectException(InvalidValueException::class);
        $instance = QrPayment::fromAccountAndBankCode(1325090011, 3030);
        $instance->getQrString();
    }

    public function testVariableSymbol()
    {
        $this->instance->setVariableSymbol(789);
        self::assertEquals(789, $this->instance->getVariableSymbol());
        self::assertEquals("{$this->getDefaultEmptyString()}*X-VS:789", $this->instance->getQrString());

        $this->instance->setVariableSymbol(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());
    }

    public function testSpecificSymbol()
    {
        $this->instance->setSpecificSymbol(7890);
        self::assertEquals(7890, $this->instance->getSpecificSymbol());
        self::assertEquals("{$this->getDefaultEmptyString()}*X-SS:7890", $this->instance->getQrString());

        $this->instance->setSpecificSymbol(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());
    }

    public function testConstantSymbol()
    {
        $this->instance->setConstantSymbol(741);
        self::assertEquals(741, $this->instance->getConstantSymbol());
        self::assertEquals("{$this->getDefaultEmptyString()}*X-KS:741", $this->instance->getQrString());

        $this->instance->setConstantSymbol(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());
    }

    public function testCurrency()
    {
        self::assertIsString($this->instance->getCurrency());

        $this->instance->setCurrency('EUR');
        self::assertEquals(
            "SPD*1.0*ACC:{$this->getIban()}*AM:0.00*CC:EUR*X-PER:7",
            $this->instance->getQrString()
        );

        $this->expectException(InvalidValueException::class);
        $this->instance->setCurrency('E*R');
        $this->instance->getQrString();
    }

    public function testComment()
    {
        $this->instance->setComment('test comment');
        self::assertEquals('test comment', $this->instance->getComment());
        self::assertEquals("{$this->getDefaultEmptyString()}*MSG:test comment", $this->instance->getQrString());

        $this->instance->setComment(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());

        $this->expectException(InvalidValueException::class);
        $this->instance->setComment('test*test');
        $this->instance->getQrString();
    }

    public function testRepeat()
    {
        self::assertIsInt($this->instance->getRepeat());

        $this->instance->setRepeat(5);
        self::assertEquals(
            "SPD*1.0*ACC:{$this->getIban()}*AM:0.00*CC:CZK*X-PER:5",
            $this->instance->getQrString()
        );
    }

    public function testInternalId()
    {
        $this->instance->setInternalId('abcde');
        self::assertEquals('abcde', $this->instance->getInternalId());
        self::assertEquals("{$this->getDefaultEmptyString()}*X-ID:abcde", $this->instance->getQrString());

        $this->instance->setInternalId(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());

        $this->expectException(InvalidValueException::class);
        $this->instance->setInternalId('id*id');
        $this->instance->getQrString();
    }

    public function testDueDate()
    {
        self::assertInstanceOf(DateTimeInterface::class, $this->instance->getDueDate());
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());

        $dueDate = new DateTimeImmutable('2025-01-07');
        $this->instance->setDueDate($dueDate);
        self::assertEquals($dueDate, $this->instance->getDueDate());
        self::assertEquals("{$this->getDefaultEmptyString()}*DT:20250107", $this->instance->getQrString());

        $this->instance->setDueDate(null);
        self::assertInstanceOf(DateTimeInterface::class, $this->instance->getDueDate());
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());
    }

    public function testAmount()
    {
        self::assertIsFloat($this->instance->getAmount());

        $this->instance->setAmount(5);
        self::assertEquals(
            "SPD*1.0*ACC:{$this->getIban()}*AM:5.00*CC:CZK*X-PER:7",
            $this->instance->getQrString()
        );

        $this->instance->setAmount(5.351);
        self::assertEquals(
            "SPD*1.0*ACC:{$this->getIban()}*AM:5.35*CC:CZK*X-PER:7",
            $this->instance->getQrString()
        );

        $this->instance->setAmount(5.355);
        self::assertEquals(
            "SPD*1.0*ACC:{$this->getIban()}*AM:5.36*CC:CZK*X-PER:7",
            $this->instance->getQrString()
        );
    }

    public function testIban()
    {
        self::assertInstanceOf(IbanInterface::class, $this->instance->getIban());
        self::assertEquals($this->getIban(), $this->instance->getIban());

        $this->instance->setIban(new IBAN('CZ6130300000001325090096'));
        self::assertEquals(
            'SPD*1.0*ACC:CZ6130300000001325090096*AM:0.00*CC:CZK*X-PER:7',
            $this->instance->getQrString()
        );

        $this->expectException(InvalidValueException::class);
        $this->instance->setIban(new class implements IbanInterface {
            public function __toString()
            {
                return $this->asString();
            }

            public function asString(): string
            {
                return 'CZ******';
            }

            public function getValidator(): ?ValidatorInterface
            {
                return null;
            }
        });
        $this->instance->getQrString();
    }

    public function testPayeeName()
    {
        $this->instance->setPayeeName('Random Dude');
        self::assertEquals('Random Dude', $this->instance->getPayeeName());
        self::assertEquals("{$this->getDefaultEmptyString()}*RN:Random Dude", $this->instance->getQrString());

        $this->instance->setPayeeName(null);
        self::assertEquals($this->getDefaultEmptyString(), $this->instance->getQrString());

        $this->expectException(InvalidValueException::class);
        $this->instance->setPayeeName('Random*Dude');
        $this->instance->getQrString();
    }

    private function getIban(): IBAN
    {
        return new IBAN('CZ5530300000001325090010');
    }

    private function getDefaultEmptyString(): string
    {
        return "SPD*1.0*ACC:{$this->getIban()}*AM:0.00*CC:CZK*X-PER:7";
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
