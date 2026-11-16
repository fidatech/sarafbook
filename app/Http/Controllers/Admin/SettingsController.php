<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{

    public function index()
    {
        $setting = Setting::firstOrCreate([]);
        $currencies = Currency::all();

        return view('admin.settings.index', compact('setting', 'currencies'));
    }

    public function update(Request $request)
    {
        $setting = Setting::first();
        $data = $request->validate([
            'company_name' => 'nullable|string',
            'contact' => 'nullable|string',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'default_language' => 'required|string',
            'currency' => 'required|exists:currencies,id',
            'logo' => 'nullable|image|max:2048',
            'note_en' => 'nullable|string',
            'note_fa' => 'nullable|string',
            'note_ps' => 'nullable|string',
            'round_amount' => 'nullable|boolean',
        ]);

        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = $logo->getClientOriginalName();

            // Delete existing logo
            if (isset($setting->logo) && $setting->logo != '' && file_exists(public_path('images/' . $setting->logo))) {
                unlink(public_path('images/' . $setting->logo));
            }

            $logo->move(public_path('images'), $filename);
            $data['logo'] = $filename;
        }

        $data['round_amount'] = $request->has('round_amount') ? 1 : 0;

        $setting->update($data);

        Session::put('locale', $data['default_language']);

        cache()->forget('app_setting');
        return back()->with('success', __('administration.settings_updated'));
    }
}
