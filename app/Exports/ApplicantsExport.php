<?php

namespace App\Exports;

use App\Models\Applicant;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ApplicantsExport implements FromCollection, WithHeadings
{
    protected $applicants;

    public function __construct($applicants)
    {
        $this->applicants = $applicants;
    }

    public function collection()
    {
        return $this->applicants;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User ID',
            'Name',
            'Email',
            'Car ID',
            'Car Name',
            'Path',
            'Purpose',
            'Submission Date',
            'Expiry Date',
            'Status',
            'Notes',
        ];
    }
}
