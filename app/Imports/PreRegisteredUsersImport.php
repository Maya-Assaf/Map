<?php

namespace App\Imports;

use App\Models\PreRegisteredUser;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use illuminate\support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class PreRegisteredUsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        //  dd($row);
         // Log every single row
    // Log::info('Row data: ', [
    //     'email' => $row['email'] ?? 'NULL',
    //     'department' => $row['department'] ?? 'NULL',
    //     'position' => $row['your_position'] ?? 'NULL',
    //     'all_keys' => array_keys($row),
    //     'all_values' => array_values($row)
    // ]);

    // Skip rows where email is empty or null
        if (empty($row['email']) || is_null($row['email'])) {
            return null; // Skip this row
        }


       return new PreRegisteredUser([
            'name' => $row['name'],
            'email' => $row['email'],
            'position' => $row['your_position'] ?? null,
            'department' => $row['department'] ?? null,
            'layer' => $row['Layer'] ?? 'Other',
            'status' => $row['status'],

        ]);
    }
}
