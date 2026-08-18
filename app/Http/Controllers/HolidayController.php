<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('holiday_date')->get();

        return view('hr.holidays', compact('holidays'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'holiday_date' => 'required|date|unique:holidays,holiday_date,NULL,id,company_id,'.auth()->user()->company_id,
        ]);
        Holiday::create($data);

        return back()->with('success', 'Holiday saved.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Holiday removed.');
    }
}
