<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function show(Request $r): View
    {
        return view('store.account', ['user' => $r->user(), 'addresses' => $r->user()->addresses, 'orders' => $r->user()->orders()->latest()->paginate(10)]);
    }

    public function update(Request $r): RedirectResponse
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'phone' => 'nullable|string|max:30']);
        $r->user()->update($data);

        return back()->with('status', __('Profile saved.'));
    }

    public function address(Request $r): RedirectResponse
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'phone' => 'required|string|max:30', 'address' => 'required|string|max:255', 'district' => 'required|string|max:100', 'postcode' => 'nullable|string|max:20']);
        $r->user()->addresses()->create($data);

        return back()->with('status', __('Address saved.'));
    }

    public function removeAddress(Request $r, Address $address): RedirectResponse
    {
        abort_unless($address->user_id === $r->user()->id, 403);
        $address->delete();

        return back()->with('status', __('Address removed.'));
    }
}
