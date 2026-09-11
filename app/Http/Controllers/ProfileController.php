<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $validated = $request->validated();

        $request->user()->update($validated);

        return redirect()->route('profile.edit')->with('success', 'プロフィールを更新しました。');
    }
}
