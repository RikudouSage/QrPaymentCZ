<?php

namespace Rikudou\CzQrPayment;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use Endroid\QrCode\QrCode;
use InvalidArgumentException;
use JetBrains\PhpStorm\Deprecated;
use Rikudou\CzQrPayment\Exception\InvalidValueException;
use Rikudou\CzQrPayment\Exception\MissingLibraryException;
use Rikudou\Iban\Iban\CzechIbanAdapter;
use Rikudou\Iban\Iban\IbanInterface;
use Rikudou\QrPayment\QrPaymentInterface;
use Rikudou\QrPaymentQrCodeProvider\EndroidQrCode3;
use Rikudou\QrPaymentQrCodeProvider\Exception\NoProviderFoundException;
use Rikudou\QrPaymentQrCodeProvider\GetQrCodeTrait;
use TypeError;

final class QrPayment implements QrPaymentInterface
{
    use GetQrCodeTrait;

    /**
     * @var int|string|null
     */
    private $variableSymbol = null;

    /**
     * @var int|string|null
     */
    private $specificSymbol = null;

    /**
     * @var int|string|null
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
    private $amount = 0.0;

    /**
     * @var IbanInterface
     */
    private $iban;

    /**
     * @var string|null
     */
    private $payeeName = null;

    /**
     * @var bool
     */
    private $instantPayment = false;

    /**
     * @param IbanInterface            $iban
     * @param array<string,mixed>|null $options
     *
     * @throws InvalidValueException
     * @throws InvalidArgumentException
     */
    public function __construct(IbanInterface $iban, ?array $options = null)
    {
        $this->iban = $iban;
        if ($options !== null) {
            $this->setOptions($options);
        }
    }

    /**
     * @param array<string,mixed> $options
     *
     * @throws InvalidValueException
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $key => $value) {
            $method = sprintf('set%s', ucfirst($key));
            if (!method_exists($this, $method)) {
                throw new InvalidArgumentException("The property '{$key}' is not valid");
            }
            Closure::fromCallable([$this, $method])->call($this, $value);
        }

        $this->checkProperties();

        return $this;
    }

    /**
     * @throws InvalidValueException
     */
    public function getQrString(): string
    {
        $this->checkProperties();

        if ($this->iban->getValidator() !== null && !$this->iban->getValidator()->isValid()) {
            throw new InvalidValueException('The IBAN is not a valid IBAN');
        }

        $qrString = 'SPD*1.0*';
        $qrString .= sprintf('ACC:%s*', $this->iban);
        $qrString .= sprintf('AM:%.2F*', $this->amount);
        $qrString .= sprintf('CC:%s*', strtoupper($this->currency));
        $qrString .= sprintf('X-PER:%d*', $this->repeat);

        if ($this->comment !== null) {
            $qrString .= sprintf('MSG:%.60s*', $this->comment);
        }
        if ($this->internalId !== null) {
            $qrString .= sprintf('X-ID:%s*', $this->internalId);
        }
        if ($this->variableSymbol !== null) {
            $qrString .= sprintf('X-VS:%s*', $this->variableSymbol);
        }
        if ($this->specificSymbol !== null) {
            $qrString .= sprintf('X-SS:%s*', $this->specificSymbol);
        }
        if ($this->constantSymbol !== null) {
            $qrString .= sprintf('X-KS:%s*', $this->constantSymbol);
        }
        if ($this->payeeName !== null) {
            $qrString .= sprintf('RN:%s*', $this->payeeName);
        }
        if ($this->dueDate !== null) {
            $qrString .= sprintf('DT:%s*', $this->dueDate->format('Ymd'));
        }
        if ($this->instantPayment) {
            $qrString .= 'PT:IP*';
        }

        return substr($qrString, 0, -1);
    }

    #[Deprecated('This method has been deprecated, please use getQrCode()', '%class%->getQrCode()->getRawObject()')]
    public function getQrImage(): QrCode
    {
        try {
            $code = $this->getQrCode();
            if (!$code instanceof EndroidQrCode3) {
                throw new MissingLibraryException('Error: library endroid/qr-code is not loaded or is not a 3.x version. For newer versions please use method getQrCode()');
            }
            // @codeCoverageIgnoreStart
        } catch (NoProviderFoundException $e) {
            throw new MissingLibraryException('Error: library endroid/qr-code is not loaded.');
            // @codeCoverageIgnoreEnd
        }

        $raw = $code->getRawObject();
        assert($raw instanceof QrCode);

        return $raw;
    }

    public static function fromAccountAndBankCode(string $accountNumber, string $bankCode): self
    {
        return new self(new CzechIbanAdapter($accountNumber, $bankCode));
    }

    /**
     * @return int|string|null
     */
    public function getVariableSymbol()
    {
        return $this->variableSymbol;
    }

    /**
     * @param int|string|null $variableSymbol
     *
     * @return $this
     */
    public function setVariableSymbol($variableSymbol): self
    {
        if (!is_string($variableSymbol) && !is_int($variableSymbol) && $variableSymbol !== null) {
            throw new TypeError(sprintf('Variable symbol must be an integer, string or null, %s given', gettype($variableSymbol)));
        }
        $this->variableSymbol = $variableSymbol;

        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getSpecificSymbol()
    {
        return $this->specificSymbol;
    }

    /**
     * @param int|string|null $specificSymbol
     *
     * @return $this
     */
    public function setSpecificSymbol($specificSymbol): self
    {
        if (!is_string($specificSymbol) && !is_int($specificSymbol) && $specificSymbol !== null) {
            throw new TypeError(sprintf('Specific symbol must be an integer, string or null, %s given', gettype($specificSymbol)));
        }
        $this->specificSymbol = $specificSymbol;

        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getConstantSymbol()
    {
        return $this->constantSymbol;
    }

    /**
     * @param int|string|null $constantSymbol
     *
     * @return $this
     */
    public function setConstantSymbol($constantSymbol): self
    {
        if (!is_string($constantSymbol) && !is_int($constantSymbol) && $constantSymbol !== null) {
            throw new TypeError(sprintf('Constant symbol must be an integer, string or null, %s given', gettype($constantSymbol)));
        }
        $this->constantSymbol = $constantSymbol;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getRepeat(): int
    {
        return $this->repeat;
    }

    public function setRepeat(int $repeat): self
    {
        $this->repeat = $repeat;

        return $this;
    }

    public function getInternalId(): ?string
    {
        return $this->internalId;
    }

    public function setInternalId(?string $internalId): self
    {
        $this->internalId = $internalId;

        return $this;
    }

    public function getDueDate(): DateTimeInterface
    {
        if ($this->dueDate === null) {
            return new DateTimeImmutable();
        }

        return $this->dueDate;
    }

    public function setDueDate(?DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getIban(): IbanInterface
    {
        return $this->iban;
    }

    public function setIban(IbanInterface $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getPayeeName(): ?string
    {
        return $this->payeeName;
    }

    public function setPayeeName(?string $payeeName): self
    {
        $this->payeeName = $payeeName;

        return $this;
    }

    public function isInstantPayment(): bool
    {
        return $this->instantPayment;
    }

    public function setInstantPayment(bool $isInstant): self
    {
        $this->instantPayment = $isInstant;

        return $this;
    }

    /**
     * Checks all properties for asterisk and throws exception if asterisk
     * is found
     *
     * @throws InvalidValueException
     */
    private function checkProperties(): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            if (
                (is_string($value) || (is_object($value) && method_exists($value, '__toString')))
                && strpos((string) $value, '*') !== false
            ) {
                throw new InvalidValueException("Error: properties cannot contain asterisk (*). Property {$property} contains it.");
            }
        }
    }
}
