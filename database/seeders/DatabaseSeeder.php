<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

use Database\Seeders\PermissionsTableSeeder;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\ConnectRelationshipsSeeder;
use Database\Seeders\UsersTableSeeder;


class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   *
   * @return void
   */
  public function run()
  {
    // \App\Models\User::factory(10)->create();

    Model::unguard();
    $this->call(PermissionsTableSeeder::class);
    $this->call(RolesTableSeeder::class);
    $this->call(ConnectRelationshipsSeeder::class);
    $this->call(UsersTableSeeder::class);
   /*  $this->call(CustomerSeeder::class);
    $this->call(SimProSeeder::class); */

    Model::reguard();
  }
}
