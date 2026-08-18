<?php

namespace Tests\Unit;

use App\Support\YoPayments;
use PHPUnit\Framework\TestCase;

class YoPaymentsTest extends TestCase
{
    public function test_it_normalizes_ugandan_msisdn(): void
    {
        $this->assertSame('256772123456', YoPayments::normalizeMsisdn('0772123456'));
        $this->assertSame('256772123456', YoPayments::normalizeMsisdn('+256 772 123 456'));
        $this->assertSame('256772123456', YoPayments::normalizeMsisdn('772123456'));
    }

    public function test_it_guesses_mtn_and_airtel(): void
    {
        $this->assertSame('mtn_momo', YoPayments::guessMethod('0772123456'));
        $this->assertSame('airtel_money', YoPayments::guessMethod('0752123456'));
        $this->assertSame('airtel_money', YoPayments::guessMethod('0700123456'));
    }
}
