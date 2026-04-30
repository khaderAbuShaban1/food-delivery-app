<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $restaurants = Restaurant::all();
        $categories = ['وجبات رئيسية', 'إضافات', 'مشروبات', 'حلويات'];

        $menuData = [
            'Fast Food' => [
                ['name' => 'برجر كلاسيك', 'price' => 18, 'description' => 'برجر لحم طازج مع خبز و صوص سريرات'],
                ['name' => 'برجر دبل', 'price' => 25, 'description' => 'برجر مزدوج مع جبن شيدر'],
                ['name' => 'دجاج مقلي', 'price' => 15, 'description' => 'قطع دجاج مقرمشة'],
                ['name' => 'بطاطس كبير', 'price' => 8, 'description' => 'بطاطس مقلية محمرة'],
                ['name' => 'كولا', 'price' => 5, 'description' => 'مشروب غازي'],
                ['name' => 'عصير برتقال', 'price' => 7, 'description' => 'عصير طازج'],
            ],
            'Italian' => [
                ['name' => 'بيتزا مارغريتا', 'price' => 30, 'description' => 'بيتزا بالجبن و الطماطم'],
                ['name' => 'سباغيتي بولونية', 'price' => 25, 'description' => 'سباغيتي مع صلصة اللحم'],
                ['name' => 'لازانيا', 'price' => 28, 'description' => 'لازانيا إيطالية'],
                ['name' => 'سلطة سيزر', 'price' => 15, 'description' => 'سلطة خضراء مع صوص'],
                ['name' => 'كوكتيل', 'price' => 10, 'description' => 'مشروب فواكه'],
                ['name' => 'آيس كريم', 'price' => 12, 'description' => 'آيس كريم بالشوكولاتة'],
            ],
            'Arabic' => [
                ['name' => 'كبسة دجاج', 'price' => 22, 'description' => 'أرز مع دجاج والتوابل'],
                ['name' => 'مندي', 'price' => 28, 'description' => 'لحم مع أرز على الطريقة اليمنية'],
                ['name' => 'فتة حمص', 'price' => 18, 'description' => 'فتة بالحمص ولبن'],
                ['name' => 'حمص', 'price' => 8, 'description' => 'حمص بالطحينة'],
                ['name' => 'شاي مغربي', 'price' => 5, 'description' => 'شاي بالنعناع'],
                ['name' => 'قهوة عربية', 'price' => 4, 'description' => 'قهوة بالهيل'],
            ],
            'default' => [
                ['name' => 'وجبة مشوية', 'price' => 20, 'description' => 'وجبة مشوية متنوعة'],
                ['name' => 'سلطة خضراء', 'price' => 12, 'description' => 'سلطة طازجة'],
                ['name' => 'أرز ابيض', 'price' => 8, 'description' => 'أرز مطبوخ'],
                ['name' => 'مشروب', 'price' => 5, 'description' => 'مشروب بارد'],
                ['name' => 'حلوى', 'price' => 10, 'description' => 'حلوى عربية'],
                ['name' => 'ماء', 'price' => 3, 'description' => 'ماء صغير'],
            ],
        ];

        foreach ($restaurants as $restaurant) {
            $category = $restaurant->category ?? 'default';
            $items = $menuData[$category] ?? $menuData['default'];

            foreach ($items as $item) {
                MenuItem::updateOrCreate([
                    'restaurant_id' => $restaurant->id,
                    'name' => $item['name'],
                ], [
                    'price' => $item['price'],
                    'description' => $item['description'],
                    'category' => $categories[array_rand($categories)],
                ]);
            }
        }

        echo "Created menu items for " . Restaurant::count() . " restaurants\n";
        echo "Total menu items: " . MenuItem::count() . "\n";
    }
}