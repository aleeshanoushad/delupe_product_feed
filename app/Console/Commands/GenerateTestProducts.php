<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateTestProducts extends Command
{
    protected $signature = 'products:generate {count=100000}';

    protected $description = 'Generate large JSON product file';

    public function handle()
    {
        $count = (int) $this->argument('count');

        $products = [];
        $this->info("Generated {$count} products");


        $file = fopen(base_path('products_large.json'), 'w');

        fwrite($file, '[');

        for ($i = 1; $i <= $count; $i++) {

            $product = [
                'id' => $i,
                'merchant_id' => 'merchant_' . str_pad((string) rand(1,1000), 3, '0', STR_PAD_LEFT),
                'name' => 'Product ' . $i,
                'link' => "https://shop.example.com/products/{$i}",
                'image_link' => "https://shop.example.com/images/{$i}.jpg",
                'price' => rand(100,10000) / 100,
                'currency' => ['USD','EUR','GBP'][array_rand(['USD','EUR','GBP'])],
            ];

            if ($i > 1) {
                fwrite($file, ',');
            }

            fwrite($file, json_encode($product));

            if ($i % 1000 === 0) {
                $this->info("Generated {$i} records...");
            }
        }

        fwrite($file, ']');

        fclose($file);

        
        // for ($i = 1; $i <= $count; $i++) {
        //     $products[] = [
        //         'id' => $i,
        //         'merchant_id' => 'merchant_' . str_pad(rand(1,1000), 3, '0', STR_PAD_LEFT),
        //         'name' => 'Product ' . $i,
        //         'link' => "https://shop.example.com/products/{$i}",
        //         'image_link' => "https://shop.example.com/images/{$i}.jpg",
        //         'price' => rand(100,10000) / 100,
        //         'currency' => ['USD','EUR','GBP'][array_rand(['USD','EUR','GBP'])],
        //     ];
        // }

        // file_put_contents(
        //     storage_path('app/products_large.json'),
        //     json_encode($products, JSON_PRETTY_PRINT)
        // );

        $this->info("Generated {$count} products");
    }
}