<?php

namespace rikudou\CzQrPayment;

use DateTimeImmutable;
use DateTimeInterface;
use Endroid\QrCode\QrCode;
use InvalidArgumentException;
use rikudou\CzQrPayment\Exception\QrPaymentException;
use Rikudou\Iban\Iban\CzechIbanAdapter;
use Rikudou\Iban\Iban\IbanInterface;
use Rikudou\QrPayment\QrPaymentInterface;

final class QrPayment implements QrPaymentInterface
{
    /**
     * @var int|null
     */
    private $variableSymbol = null;

    /**
     * @var int|null
     */
    private $specificSymbol = null;

    /**
     * @var int|null
     */
    private $constantSymbol = null;

    /**
     * @var string
     */
    private $currency = 'CZK';

    /**
     * @var string|null
     */
    private $comment = null;

    /**
     * @var int
     */
    private $repeat = 7;

    /**
     * @var string|null
     */
    private $internalId = null;

    /**
     * @var DateTimeInterface|null
     */
    private $dueDate = null;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $country = 'CZ';

    /**
     * @var IbanInterface
     */
    private $iban;

    /**
     * @var string
     */
    private $payeeName;

    public function __construct(IbanInterface $iban, ?array $options = null)
    {
        $this->iban = $iban;
        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     * @param array $options
     *
     * @throws QrPaymentException
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = sprintf('set%s', ucfirst($key));
            if (method_exists($this, $method)) {
                /** @var callable $callable */
                $callable = [$this, $method];
                call_user_func($callable, $value);
            } else {
                throw new InvalidArgumentException("The property '{$key}' is not valid");
            }
        }

        $this->checkProperties();

        return $this;
    }

    /**
     * @throws QrPaymentException
     *
     * @return string
     */
    public function getQrString(): string
    {
        $this->checkProperties();

        $qr = 'SPD*1.0*';
        $qr .= sprintf('ACC:%s*', $this->getIban());
        $qr .= sprintf('AM:%.2f*', $this->amount);
        $qr .= sprintf('CC:%s*', strtoupper($this->currency));
        $qr .= sprintf('X-PER:%d*', $this->repeat);

        if ($this->comment !== null) {
            $qr .= sprintf('MSG:%.60s*', $this->comment);
        }
        if ($this->internalId !== null) {
            $qr .= sprintf('X-ID:%s*', $this->internalId);
        }
        if ($this->variableSymbol !== null) {
            $qr .= sprintf('X-VS:%d*', $this->variableSymbol);
        }
        if ($this->specificSymbol !== null) {
            $qr .= sprintf('X-SS:%d*', $this->specificSymbol);
        }
        if ($this->constantSymbol !== null) {
            $qr .= sprintf('X-KS:%d*', $this->constantSymbol);
        }
        if ($this->payeeName !== null) {
            $qr .= sprintf('RN:%s*', $this->payeeName);
        }
        if ($this->dueDate !== null) {
            $qr .= sprintf('DT:%s*', $this->dueDate->format('Ymd'));
        }

        return substr($qr, 0, -1);
    }

    /**
     * @throws QrPaymentException
     *
     * @return QrCode
     */
    public function getQrImage(): QrCode
    {
        if (!class_exists("Endroid\QrCode\QrCode")) {
            throw new QrPaymentException('Error: library endroid/qr-code is not loaded.', QrPaymentException::ERR_MISSING_LIBRARY);
        }

        return new QrCode($this->getQrString());
    }

    /**
     * @param string $accountNumber
     * @param string $bankCode
     *
     * @return self
     */
    public static function fromAccountAndBankCode(string $accountNumber, string $bankCode)
    {
        return new self(new CzechIbanAdapter($accountNumber, $bankCode));
    }

    /**
     * @return int|null
     */
    public function getVariableSymbol(): ?int
    {
        return $this->variableSymbol;
    }

    /**
     * @param int|null $variableSymbol
     *
     * @return QrPayment
     */
    public function setVariableSymbol(?int $variableSymbol): QrPayment
    {
        $this->variableSymbol = $variableSymbol;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSpecificSymbol(): ?int
    {
        return $this->specificSymbol;
    }

    /**
     * @param int|null $specificSymbol
     *
     * @return QrPayment
     */
    public function setSpecificSymbol(?int $specificSymbol): QrPayment
    {
        $this->specificSymbol = $specificSymbol;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getConstantSymbol(): ?int
    {
        return $this->constantSymbol;
    }

    /**
     * @param int|null $constantSymbol
     *
     * @return QrPayment
     */
    public function setConstantSymbol(?int $constantSymbol): QrPayment
    {
        $this->constantSymbol = $constantSymbol;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return QrPayment
     */
    public function setCurrency(string $currency): QrPayment
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * @param string|null $comment
     *
     * @return QrPayment
     */
    public function setComment(?string $comment): QrPayment
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return int
     */
    public function getRepeat(): int
    {
        return $this->repeat;
    }

    /**
     * @param int $repeat
     *
     * @return QrPayment
     */
    public function setRepeat(int $repeat): QrPayment
    {
        $this->repeat = $repeat;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    /**
     * @param string|null $internalId
     *
     * @return QrPayment
     */
    public function setInternalId(?string $internalId): QrPayment
    {
        $this->internalId = $internalId;

        return $this;
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getDueDate(): DateTimeInterface
    {
        if ($this->dueDate === null) {
            return new DateTimeImmutable();
        }

        return $this->dueDate;
    }

    /**
     * @param DateTimeInterface|null $dueDate
     *
     * @return QrPayment
     */
    public function setDueDate(?DateTimeInterface $dueDate): QrPayment
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     *
     * @return QrPayment
     */
    public function setAmount(float $amount): QrPayment
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return QrPayment
     */
    public function setCountry(string $country): QrPayment
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return IbanInterface
     */
    public function getIban(): IbanInterface
    {
        return $this->iban;
    }

    /**
     * @param IbanInterface $iban
     *
     * @return QrPayment
     */
    public function setIban(IbanInterface $iban): QrPayment
    {
        $this->iban = $iban;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayeeName(): string
    {
        return $this->payeeName;
    }

    /**
     * @param string $payeeName
     *
     * @return QrPayment
     */
    public function setPayeeName(string $payeeName): QrPayment
    {
        $this->payeeName = $payeeName;

        return $this;
    }

    /**
     * Checks all properties for asterisk and throws exception if asterisk
     * is found
     *
     * @throws QrPaymentException
     */
    private function checkProperties(): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (
                (is_string($value) || (is_object($value) && method_exists($value, '__toString')))
                && strpos($value, '*') !== false
            ) {
                throw new QrPaymentException("Error: properties cannot contain asterisk (*). Property {$property} contains it.", QrPaymentException::ERR_ASTERISK);
            }
        }
    }
}
