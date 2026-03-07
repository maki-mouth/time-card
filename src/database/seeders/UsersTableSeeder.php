<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => '山田太郎',
            'email' => 'yamada@example.com',
            'password' => bcrypt('password789'),
        ]);

            DB::table('users')->insert([
                'name' => '佐藤花子',
                'email' => 'sato@example.com',
                'password' => bcrypt('password456'),
                'role' => 'admin', 
            ]);
    }
}
