<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemImagesSeeder extends Seeder
{
    public function run(): void
    {
        $imageUrls = [
            'برجر كلاسيك' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=200&fit=crop',
            'برجر دبل' => 'https://images.unsplash.com/photo-1553979459-d9f8c053d0d9?w=200&h=200&fit=crop',
            'دجاج مقلي' => 'https://images.unsplash.com/photo-1626649228040-8d1d210eko2e?w=200&h=200&fit=crop',
            'بطاطس كبير' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=200&h=200&fit=crop',
            'كولا' => 'https://images.unsplash.com/photo-1581006853106-6f0c1a01c7c5?w=200&h=200&fit=crop',
            'عصير برتقال' => 'https://images.unsplash.com/photo-1621506563064-6f60f40a2b57?w=200&h=200&fit=crop',
            'بيتزا مارغريتا' => 'https://images.unsplash.com/photo-1604061838517-89304831d4e0?w=200&h=200&fit=crop',
            'سباغيتي بولونية' => 'https://images.unsplash.com/photo-1551892374-ecf8754cf8b0?w=200&h=200&fit=crop',
            'لازانيا' => 'https://images.unsplash.com/photo-1619895092538-128341789043?w=200&h=200&fit=crop',
            'سلطة سيزر' => 'https://images.unsplash.com/photo-1546793665-c74683cb3394?w=200&h=200&fit=crop',
            'كوكتيل' => 'https://images.unsplash.com/photo-1513558161293-f042cde49d88?w=200&h=200&fit=crop',
            'آيس كريم' => 'https://images.unsplash.com/photo-1567206563064-6f60f40a2b57?w=200&h=200&fit=crop',
            'كبسة دجاج' => 'https://images.unsplash.com/photo-1599487488170-8112d77e5a2a?w=200&h=200&fit=crop',
            'مندي' => 'https://images.unsplash.com/photo-1604909052743-94e838986d80?w=200&h=200&fit=crop',
            'فتة حمص' => 'https://images.unsplash.com/photo-1599021457063-31fb6f95b507?w=200&h=200&fit=crop',
            'حمص' => 'https://images.unsplash.com/photo-1584571428301-16a37c6bd47d?w=200&h=200&fit=crop',
            'شاي مغربي' => 'https://images.unsplash.com/photo-1556679343-c7306c0a5d00?w=200&h=200&fit=crop',
            'قهوة عربية' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=200&h=200&fit=crop',
            'وجبة مشوية' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=200&h=200&fit=crop',
            'سلطة خضراء' => 'https://images.unsplash.com/photo-1512621776950-a26d341842b7?w=200&h=200&fit=crop',
            'أرز ابيض' => 'https://images.unsplash.com/photo-1516684732162-796a6df6aac8?w=200&h=200&fit=crop',
            'مشروب' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=200&h=200&fit=crop',
            'حلوى' => 'https://images.unsplash.com/photo-1551024601-bec78aea7043?w=200&h=200&fit=crop',
            'ماء' => 'https://images.unsplash.com/photo-1548839140-29a28af1d027?w=200&h=200&fit=crop',
            'Burger' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=200&h=200&fit=crop',
            'Pizza' => 'https://images.unsplash.com/photo-1604061838517-89304831d4e0?w=200&h=200&fit=crop',
            'Fries' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=200&h=200&fit=crop',
            'fc' => 'https://images.unsplash.com/photo-1626649228040-8d1d210eko2e?w=200&h=200&fit=crop',
        ];

        $defaultImage = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=200&h=200&fit=crop';

        $menuItems = MenuItem::all();
        foreach ($menuItems as $item) {
            $imageUrl = $imageUrls[$item->name] ?? $defaultImage;
            $item->update(['image' => $imageUrl]);
        }

        echo "Updated " . $menuItems->count() . " menu items with images\n";
    }
}