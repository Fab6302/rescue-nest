<?php

namespace App\Support;

use App\Models\Entry;
use App\Models\User;
use Illuminate\Support\Str;

class DemoDirectory
{
    public static function seed(User $gallery): void
    {
        Entry::where('user_id', $gallery->id)->where('kind', 'pet')
            ->whereIn('photo', ['gallery/cat-5.jpg', 'gallery/cat-10.jpg'])->get()
            ->each(function (Entry $mew) {
                $details = $mew->details ?? [];
                if (($details['breed'] ?? 'Unknown') !== 'Unknown') {
                    return;
                }
                $mew->update(['details' => [
                    'breed' => 'Calico', 'age' => 3, 'weight' => 3,
                    'diet' => 'Fish and chicken',
                    'favorites' => 'Grilled chicken and tandoori are mentioned among Mew’s favorites.',
                    'father' => 'Peu — Persian', 'mother' => 'Pakhi — pure Deshi breed',
                ] + $details]);
            });

        $owner = User::firstOrCreate(['email' => 'directory@example.invalid'], [
            'name' => 'RescueNest Sample Directory', 'password' => Str::random(48),
        ]);
        $shops = [
            ['Pet Clinic', null, 'Clinic listing for the demo directory.'],
            ['Ichi Pet Shop', 'Banasree', 'Pet supplies listing in Banasree.'],
            ['NextGen Pet Care', 'Kalshi', 'Pet care listing in the Kalshi / ECB area.'],
        ];
        foreach ($shops as [$name, $area, $description]) {
            $shop = Entry::firstOrCreate(['kind' => 'shop', 'user_id' => $owner->id, 'title' => $name], [
                'body' => $description.' Demo listing. Hours and contact details are unverified.',
                'area' => $area,
                'details' => ($name === 'NextGen Pet Care'
                    ? ['address' => 'Kalshi / ECB', 'secondary_area' => 'ECB']
                    : ['address' => $area ?? 'Area to be confirmed']),
            ]);
            if ($name !== 'Ichi Pet Shop') {
                continue;
            }
            foreach ([
                ['Cat Food', 'Food', 350], ['Cat Treat', 'Treats', 150],
                ['Cat Litter', 'Litter', 550], ['Cat Toy', 'Toys', 220],
            ] as [$title, $category, $price]) {
                Entry::firstOrCreate(['kind' => 'product', 'user_id' => $owner->id, 'title' => $title, 'parent_id' => $shop->id], [
                    'body' => 'Demo product. Price and stock are illustrative.',
                    'details' => ['category' => $category, 'price' => $price, 'availability' => 'available'],
                ]);
            }
        }
    }
}