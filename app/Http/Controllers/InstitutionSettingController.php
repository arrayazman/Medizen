<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InstitutionSetting;
use App\Models\Gallery;
use Illuminate\Support\Facades\Storage;

class InstitutionSettingController extends Controller
{
    public function index()
    {
        $setting = InstitutionSetting::first() ?? new InstitutionSetting();
        $galleries = Gallery::all();
        return view('settings.index', compact('setting', 'galleries'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'hospital_name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'pacs_license_code' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'watermark' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'footer' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
            'satusehat_organization_id' => 'nullable|string|max:100',
            'satusehat_client_id' => 'nullable|string|max:255',
            'satusehat_client_secret' => 'nullable|string|max:255',
            'satusehat_env' => 'nullable|in:sandbox,production',
            'display_gallery_id' => 'nullable|exists:galleries,id',
            'display_instructions' => 'nullable|array',
            'display_marquee_text' => 'nullable|string',
        ]);

        $setting = InstitutionSetting::first() ?? new InstitutionSetting();

        $setting->hospital_name = $validated['hospital_name'];
        $setting->address = $validated['address'];
        $setting->phone = $validated['phone'];
        $setting->email = $validated['email'];
        $setting->website = $validated['website'];
        $setting->pacs_license_code = $validated['pacs_license_code'];
        $setting->display_gallery_id = $validated['display_gallery_id'];

        // Filter out empty instructions
        $instructions = array_filter($request->input('display_instructions', []), function ($val) {
            return !empty(trim($val));
        });
        $setting->display_instructions = array_values($instructions);

        $setting->display_marquee_text = $validated['display_marquee_text'];

        // SATUSEHAT Settings
        $setting->satusehat_organization_id = $validated['satusehat_organization_id'];
        $setting->satusehat_client_id = $validated['satusehat_client_id'];
        $setting->satusehat_client_secret = $validated['satusehat_client_secret'];
        $setting->satusehat_env = $validated['satusehat_env'] ?? 'sandbox';

        // ... existing file upload logic ...
        if ($request->hasFile('logo')) {
            $logoName = 'rs-logo-custom.' . $request->file('logo')->extension();
            $request->file('logo')->move(public_path('img/settings'), $logoName);
            $setting->logo_path = 'img/settings/' . $logoName;
        }

        if ($request->hasFile('watermark')) {
            $watermarkName = 'watermark-custom.' . $request->file('watermark')->extension();
            $request->file('watermark')->move(public_path('img/settings'), $watermarkName);
            $setting->watermark_path = 'img/settings/' . $watermarkName;
        }

        if ($request->hasFile('footer')) {
            $footerName = 'footer-custom.' . $request->file('footer')->extension();
            $request->file('footer')->move(public_path('img/settings'), $footerName);
            $setting->footer_path = 'img/settings/' . $footerName;
        }

        $setting->save();

        return redirect()->back()->with('success', 'Setting Instansi berhasil diperbarui!');
    }

    public function activateLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        $setting = InstitutionSetting::first() ?? new InstitutionSetting();
        $setting->app_license_key = $request->license_key;

        // Dummy check for license
        if ($request->license_key === 'PRO-2026-MEDIZEN') {
            $setting->is_pro = true;
            $setting->save();
            return redirect()->back()->with('success', 'Selamat! Aplikasi Anda kini adalah Versi PRO.');
        }

        $setting->is_pro = false;
        $setting->save();
        return redirect()->back()->with('error', 'Lisensi tidak valid. Silakan hubungi pengembang.');
    }
}
