<?php

namespace Tests\Unit;

use App\Support\BankStatementService;
use App\Support\PdfText;
use PHPUnit\Framework\TestCase;

class BankStatementServiceTest extends TestCase
{
    public function test_it_parses_equity_style_statement_text(): void
    {
        $text = <<<TXT
EQUITY
Account Statement
PHILIP WAISWA
Account Number 1039100830273
Currency UGX
Transactions
Transaction Details Payment reference Value Date Credit (Money In) Debit (Money Out) Balance
Chq:002477 AIDS INFORMATION CENTRE/ PROJECT S955451 28/08/2024 9,320,100.00 9,325,100.62
APP/MTN/256774749863/724818470136/TUMUSIIME TADEO/ 724818470136 EQ724818470136 562331 28/08/2024 3,000,000.00 6,325,100.62
MOBILE MONEY CHARGES 724818470136 EQ724818470136 562331 28/08/2024 6,000.00 6,319,100.62
TXT;

        $rows = BankStatementService::parseText($text);

        $this->assertCount(3, $rows);
        $this->assertSame('2024-08-28', $rows[0]['line_date']);
        $this->assertSame('S955451', $rows[0]['reference']);
        $this->assertSame(9320100.0, $rows[0]['amount']);
        $this->assertStringContainsString('AIDS INFORMATION CENTRE', $rows[0]['description']);
        $this->assertSame(-3000000.0, $rows[1]['amount']);
        $this->assertSame('562331', $rows[1]['reference']);
        $this->assertSame(-6000.0, $rows[2]['amount']);
    }

    public function test_it_parses_wrapped_equity_rows(): void
    {
        $text = <<<TXT
Transactions
Chq:002477 AIDS INFORMATION CENTRE/
PROJECT
S955451
28/08/2024
9,320,100.00
9,325,100.62
MOBILE MONEY CHARGES
562331
28/08/2024
6,000.00
9,319,100.62
TXT;

        $rows = BankStatementService::parseText($text);

        $this->assertCount(2, $rows);
        $this->assertSame(9320100.0, $rows[0]['amount']);
        $this->assertSame(-6000.0, $rows[1]['amount']);
    }

    public function test_it_parses_multiple_transactions_on_one_line(): void
    {
        $text = 'Chq:002477 AIDS INFORMATION CENTRE/ PROJECT S955451 28/08/2024 9,320,100.00 9,325,100.62 APP/MTN/256774749863/TUMUSIIME TADEO 562331 28/08/2024 3,000,000.00 6,325,100.62 MOBILE MONEY CHARGES 562331 28/08/2024 6,000.00 6,319,100.62';
        $rows = BankStatementService::parseText($text);
        $this->assertCount(3, $rows);
        $this->assertSame(9320100.0, $rows[0]['amount']);
        $this->assertSame(-3000000.0, $rows[1]['amount']);
        $this->assertSame(-6000.0, $rows[2]['amount']);
    }

    public function test_it_reads_pdf_literal_and_utf16_hex_strings(): void
    {
        $content = '(Chq:002477 AIDS INFORMATION CENTRE/ PROJECT) Tj (S955451) Tj (28/08/2024) Tj (9,320,100.00) Tj (9,325,100.62) Tj '
            .'<00320038002F00300038002F0032003000320034> Tj';
        $text = PdfText::stringsFromContent($content);
        $this->assertStringContainsString('AIDS INFORMATION CENTRE', $text);
        $this->assertStringContainsString('28/08/2024', $text);
        $this->assertStringContainsString('9,320,100.00', $text);
    }

    public function test_it_parses_ugandan_dates_and_money(): void
    {
        $this->assertSame('2024-08-28', BankStatementService::parseDate('28/08/2024'));
        $this->assertSame(9320100.0, BankStatementService::parseMoney('9,320,100.00'));
        $this->assertSame(6000.0, BankStatementService::parseMoney('6,000.00'));
    }
}
