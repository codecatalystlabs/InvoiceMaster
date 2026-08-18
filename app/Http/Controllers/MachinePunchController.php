<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use App\Support\AttendanceService;
use Illuminate\Http\Request;

class MachinePunchController extends Controller
{
    public function cdata(Request $request)
    {
        $device = $this->deviceFromRequest($request);
        if (! $device) {
            return response("OK\n", 200, ['Content-Type' => 'text/plain']);
        }
        $device->update(['last_seen_at' => now()]);
        $table = strtoupper((string) ($request->query('table') ?? $request->input('table')));

        if ($table === 'ATTLOG') {
            $rows = AttendanceService::parseAttLog($request->getContent());
            $n = AttendanceService::ingest((int) $device->company_id, $rows, $device, 'iclock');

            return response('OK: '.$n."\n", 200, ['Content-Type' => 'text/plain']);
        }

        if ($request->query('options') || $request->isMethod('get')) {
            return response("GET OPTION FROM: {$device->serial_number}\nStamp=9999\nOpStamp=9999\nErrorDelay=60\nDelay=30\nTransTimes=00:00;14:00\nTransInterval=1\nTransFlag=111111111111\nRealtime=1\nEncrypt=0\n", 200, ['Content-Type' => 'text/plain']);
        }

        return response("OK\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function getrequest(Request $request)
    {
        $device = $this->deviceFromRequest($request);
        if ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        return response("OK\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function json(Request $request)
    {
        $device = $this->deviceFromKey($request);
        if (! $device) {
            return response()->json(['message' => 'Unknown attendance device.'], 401);
        }
        $data = $request->validate([
            'punches' => 'required|array|min:1',
            'punches.*.pin' => 'required_without:punches.*.machine_pin|string',
            'punches.*.machine_pin' => 'nullable|string',
            'punches.*.punched_at' => 'required|date',
            'punches.*.status' => 'nullable|integer',
            'punches.*.verify' => 'nullable|integer',
        ]);
        $device->update(['last_seen_at' => now()]);
        $n = AttendanceService::ingest((int) $device->company_id, $data['punches'], $device, 'api');

        return response()->json(['ok' => true, 'saved' => $n]);
    }

    protected function deviceFromRequest(Request $request): ?AttendanceDevice
    {
        $sn = $request->query('SN')
            ?? $request->query('sn')
            ?? $request->input('SN')
            ?? $request->input('sn');
        if (! $sn) {
            return null;
        }

        return AttendanceDevice::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('serial_number', $sn)
            ->first();
    }

    protected function deviceFromKey(Request $request): ?AttendanceDevice
    {
        $key = $request->bearerToken()
            ?: $request->header('X-Device-Key')
            ?: $request->input('device_key');
        if (! $key) {
            return $this->deviceFromRequest($request);
        }

        return AttendanceDevice::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('device_key', $key)
            ->first();
    }
}
