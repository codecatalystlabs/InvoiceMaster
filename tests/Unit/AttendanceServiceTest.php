<?php

namespace Tests\Unit;

use App\Support\AttendanceService;
use PHPUnit\Framework\TestCase;

class AttendanceServiceTest extends TestCase
{
    public function test_it_parses_zkteco_attlog_tabs(): void
    {
        $body = "1\t2026-06-30 08:01:12\t0\t1\n1\t2026-06-30 17:04:03\t1\t1\n";
        $rows = AttendanceService::parseAttLog($body);

        $this->assertCount(2, $rows);
        $this->assertSame('1', $rows[0]['machine_pin']);
        $this->assertSame(0, $rows[0]['status']);
        $this->assertSame(1, $rows[1]['status']);
        $this->assertSame('2026-06-30 08:01:12', $rows[0]['punched_at']->format('Y-m-d H:i:s'));
    }

    public function test_it_parses_attlog_with_split_date_and_time(): void
    {
        $body = "12  2026-06-30  08:15:00  0  1";
        $rows = AttendanceService::parseAttLog($body);

        $this->assertCount(1, $rows);
        $this->assertSame('12', $rows[0]['machine_pin']);
        $this->assertSame('2026-06-30 08:15:00', $rows[0]['punched_at']->format('Y-m-d H:i:s'));
    }
}
