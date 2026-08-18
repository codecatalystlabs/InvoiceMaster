<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDevice;
use Illuminate\Http\Request;

class AttendanceDeviceController extends Controller
{
    public function index()
    {
        $devices = AttendanceDevice::orderBy('name')->get();

        return view('hr.devices', compact('devices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:80',
            'vendor' => 'nullable|string|max:40',
            'location' => 'nullable|string|max:100',
            'work_start' => 'nullable|date_format:H:i',
            'work_end' => 'nullable|date_format:H:i',
            'late_grace_minutes' => 'nullable|integer|min:0|max:180',
        ]);
        $data['vendor'] = $data['vendor'] ?: 'zkteco';
        $data['serial_number'] = $data['serial_number'] ?: null;
        $data['work_start'] = $data['work_start'] ?? '08:00';
        $data['work_end'] = $data['work_end'] ?? '17:00';
        $data['is_active'] = true;
        AttendanceDevice::create($data);

        return back()->with('success', 'Device registered. Set the machine ADMS URL to this site, and paste the serial number.');
    }

    public function update(Request $request, AttendanceDevice $device)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'serial_number' => 'nullable|string|max:80',
            'location' => 'nullable|string|max:100',
            'work_start' => 'nullable|date_format:H:i',
            'work_end' => 'nullable|date_format:H:i',
            'late_grace_minutes' => 'nullable|integer|min:0|max:180',
            'is_active' => 'nullable|boolean',
        ]);
        $data['serial_number'] = $data['serial_number'] ?: null;
        $data['is_active'] = $request->boolean('is_active');
        $device->update($data);

        return back()->with('success', 'Device updated.');
    }

    public function destroy(AttendanceDevice $device)
    {
        $device->delete();

        return back()->with('success', 'Device removed.');
    }
}
