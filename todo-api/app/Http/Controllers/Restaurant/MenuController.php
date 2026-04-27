<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();
        $menuItems = MenuItem::where('restaurant_id', $restaurant->id)->get();

        return view('restaurant.menu', compact('restaurant', 'menuItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
        ]);

        return redirect()->route('restaurant.menu')->with('success', 'Menu item added!');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();

        $menuItem = MenuItem::where('id', $id)->where('restaurant_id', $restaurant->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $menuItem->update($request->only(['name', 'price', 'description']));

        return redirect()->route('restaurant.menu')->with('success', 'Menu item updated!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = auth()->user();
        $restaurant = Restaurant::where('user_id', $user->id)->firstOrFail();

        $menuItem = MenuItem::where('id', $id)->where('restaurant_id', $restaurant->id)->firstOrFail();
        $menuItem->delete();

        return redirect()->route('restaurant.menu')->with('success', 'Menu item deleted!');
    }
}