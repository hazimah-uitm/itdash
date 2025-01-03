<?php

namespace App\Imports;

use App\Models\Aduan;
use DateTime;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AduanImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Convert the date format from 'DD.MM.YYYY' to 'YYYY-MM-DD'
        $dateApplied = \DateTime::createFromFormat('d.m.Y', $row['date_applied']);
        $dateAppliedFormatted = $dateApplied ? $dateApplied->format('Y-m-d') : null;

        $dateCompleted = \DateTime::createFromFormat('d.m.Y', $row['date_completed']);
        $dateCompletedFormatted = $dateCompleted ? $dateCompleted->format('Y-m-d') : null;

        // Check if time columns have '-' and replace with null, keep time format as 'HH:MM'
        $timeApplied = ($row['time_applied'] == '-' || empty($row['time_applied'])) ? null : $this->formatTime($row['time_applied']);
        $timeCompleted = ($row['time_completed'] == '-' || empty($row['time_completed'])) ? null : $this->formatTime($row['time_completed']);

        // Extract the value after "- UITM KAMPUS"
        $campusValue = $row['campus_zone'];
        $splitCampusValue = explode('- UITM KAMPUS', $campusValue);
        $campus = isset($splitCampusValue[1]) ? trim($splitCampusValue[1]) : null;

        $rating = ($row['rating'] == '-' || empty($row['rating'])) ? null : (int) $row['rating'];

        return new Aduan([
            'aduan_ict_tiket' => $row['aduan_ict_ticket'],
            'complainent_name_id' => $row['complainent_name_id'],
            'complainent_category' => $row['complainent_category'],
            'aduan_category' => $row['aduan_category'],
            'aduan_subcategory' => $row['aduan_sub_category'],
            'campus' => $campus,
            'location' => $row['location'],
            'aduan_details' => $row['aduan_details'],
            'aduan_status' => $row['aduan_status'],
            'aduan_type' => $row['aduan_type'],
            'staff_duty' => $row['staff_on_duty'],
            'remark_staff_duty' => $row['remark_staff_on_duty'],
            'date_applied' => $dateAppliedFormatted,
            'time_applied' => $timeApplied,
            'date_completed' => $dateCompletedFormatted,
            'time_completed' => $timeCompleted,
            'response_time' => $row['response_time'],
            'rating' => $rating,
        ]);
    }

    private function formatTime($time)
    {
        // Check if the time is empty or invalid
        if (empty($time) || $time == '-' || $time == null) {
            return null;  // Return null if the time is missing or placeholder
        }
    
        // If the time is a decimal number (Excel time format), convert it to a valid time
        if (is_numeric($time)) {
            // Convert Excel's time to seconds of the day (multiply by 24 hours)
            $timeInSeconds = $time * 24 * 60 * 60;
    
            // Create a DateTime object based on the seconds of the day
            $formattedTime = new DateTime();
            $formattedTime->setTimestamp($timeInSeconds);
    
            // Return the time in 'H:i:s' format (24-hour time)
            return $formattedTime->format('H:i:s');
        }
    
        // Else if the time is in a string format like 'h:i:s A' (12-hour format with AM/PM)
        $formattedTime = DateTime::createFromFormat('h:i:s A', $time);  // 12-hour format with AM/PM
    
        if ($formattedTime === false) {
            // Try 24-hour format if 12-hour format fails
            $formattedTime = DateTime::createFromFormat('H:i:s', $time);  // 24-hour format
        }
    
        // If the conversion was successful, return it; otherwise, return null
        return $formattedTime ? $formattedTime->format('H:i:s') : null;
    }    
     
}
