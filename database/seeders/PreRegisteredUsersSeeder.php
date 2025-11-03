<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PreRegisteredUsersImport;

class PreRegisteredUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      
        

        //
        Excel::import(new PreRegisteredUsersImport,
            database_path('seeders/data/pre_registered_users.xlsx')
        );
       
    }
}
