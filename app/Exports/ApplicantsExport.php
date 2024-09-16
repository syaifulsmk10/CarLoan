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
        $formattedData = collect($this->applicants)->flatMap(function ($applicant) {
            $adminApprovals = $applicant['admin_approvals'];

            // Jika tidak ada admin approvals, return data applicant tanpa admin approval
            if ($adminApprovals->isEmpty()) {
                return collect([
                    [
                        $applicant['id'],
                        $applicant['user_id'],
                        $applicant['name'],
                        $applicant['email'],
                        $applicant['car_id'],
                        $applicant['car_name'],
                        $applicant['path'],
                        $applicant['purpose'],
                        $applicant['submission_date'],
                        $applicant['expiry_date'],
                        $applicant['status'],
                        '',
                        '',
                        '',
                        '',
                        '',
                    ]
                ]);
            }

            // Jika ada admin approvals, buat satu baris per admin approval
            return $adminApprovals->map(function ($approval) use ($applicant) {
                return [
                    $applicant['id'],
                    $applicant['user_id'],
                    $applicant['name'],
                    $applicant['email'],
                    $applicant['car_id'],
                    $applicant['car_name'],
                    $applicant['path'],
                    $applicant['purpose'],
                    $applicant['submission_date'],
                    $applicant['expiry_date'],
                    $applicant['status'],
                    $approval['id'],
                    $approval['user_id'],
                    $approval['admin_name'], // Admin name added here
                    $approval['approval_status'],
                    $approval['notes'],
                ];
            });
        });

        return $formattedData;
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
            'Admin Approval ID',
            'Admin Approval User ID',
            'Admin Approval User Name', // Header for admin name
            'Admin Approval Status',
            'Admin Approval Notes',
        ];
    }

}