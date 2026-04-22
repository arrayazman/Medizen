<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function provinces()
    {
        return response()->json(\DB::table('provinces')->orderBy('name')->get());
    }

    public function regencies($province_id)
    {
        return response()->json(\DB::table('regencies')->where('province_id', $province_id)->orderBy('name')->get());
    }

    public function districts($regency_id)
    {
        return response()->json(\DB::table('districts')->where('regency_id', $regency_id)->orderBy('name')->get());
    }

    public function villages($district_id)
    {
        return response()->json(\DB::table('villages')->where('district_id', $district_id)->orderBy('name')->get());
    }
}
