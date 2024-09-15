<?php

namespace App\Http\Controllers;

use App\Exports\ApplicantsExport;
use App\Models\AdminApplicantApproval;
use App\Models\AdminCar;
use App\Models\Applicant;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class DataApplicantController extends Controller
{
  
    public function index(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                "message" => "User not authenticated"
            ], 401); // Status 401 untuk unauthorized
        }
    
        if (Auth::user()->role->id == 1) {
            $userId = Auth::id();
            $applicantQuery = Applicant::with('user', 'car', 'adminApplicantApprovals')
                ->whereHas('car.adminCars', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
    
            $search = $request->input('search');
            $applicantQuery->where(function ($q) use ($search) {
                $q->where('purpose', 'LIKE', "%{$search}%")
                    ->orWhereHas('adminApplicantApprovals', function ($q) use ($search) {
                        // Mencari di kolom 'notes' dari tabel 'admin_applicant_approvals'
                        $q->where('notes', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q) use ($search) {
                        // Mencari di kolom user terkait
                        $q->where('FirstName', 'LIKE', "%{$search}%")
                            ->orWhere('LastName', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
    
            // Filter berdasarkan status
            if ($request->has('status')) {
                $status = $request->input('status');
                if (is_array($status)) {
                    $applicantQuery->whereIn('status', $status);
                } else {
                    $applicantQuery->where('status', $status);
                }
            }
    
            if ($request->has('car_id') && $request->input('car_id') !== null) {
                $carId = $request->input('car_id');
                $applicantQuery->where('car_id', $carId);
            }
    
            // Filter berdasarkan tanggal
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                $applicantQuery->whereBetween('submission_date', [$startDate, $endDate]);
            }
    
            // Paginate hasil
            $perpage = $request->input("per_page", 5);
            $applicant = $applicantQuery->paginate($perpage);
            $totalpage = $applicant->lastPage();
    
            // Ambil data mobil yang berkaitan dengan admin yang login
            $latestApplicants = DB::table('applicants')
                ->select('car_id', DB::raw('MAX(submission_date) as latest_submission_date'))
                ->groupBy('car_id');
    
            $cars = Car::with(['applicants' => function ($query) use ($latestApplicants) {
                $query->joinSub($latestApplicants, 'latest', function ($join) {
                    $join->on('applicants.car_id', '=', 'latest.car_id')
                        ->on('applicants.submission_date', '=', 'latest.latest_submission_date');
                })
                ->where('status', 'Disetujui')
                ->orderBy('submission_date', 'desc');
            }, 'applicants.user', 'adminCars'])
            ->whereHas('adminCars', function($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();
    
            $datacar = [];
            foreach ($cars as $car) {
                // Dapatkan peminjam terakhir jika ada
                $lastApplicant = $car->applicants->first();
                $borrower = $lastApplicant ? $lastApplicant->user->FirstName . ' ' . $lastApplicant->user->LastName : 'Tidak Ada';
                $expiry = $lastApplicant ? $lastApplicant->expiry_date : 'Tidak Ada';
    
                if ($car->status == "In Use") {
                    $datacar[] = [
                        'id' => $car->id,
                        'name' => $car->name_car,
                        'status_name' => $car->status,
                        'borrowed_by' => $borrower,
                        'expiry_date' => $expiry,
                        'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
                    ];
                } else {
                    $datacar[] = [
                        'car_id' => $car->id,
                        'name' => $car->name_car,
                        'status_name' => $car->status,
                        'borrowed_by' => "Tidak Ada",
                        'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
                    ];
                }
            }
    
            $applicants = $applicantQuery->get()->transform(function ($applicant) {
                $adminApprovals = $applicant->adminApplicantApprovals->mapWithKeys(function ($approval) {
                    return [
                       'id' => $approval->id,
                        'user_id' => $approval->user_id,
                        'approval_status' => $approval->approval_status,
                        'notes' => $approval->notes,
                    ];
                });
    
                return [
                    'id' => $applicant->id,
                    'user_id' => $applicant->user_id,
                    'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                    'email' => $applicant->user->email,
                    'car_id' => $applicant->car_id,
                    'car_name' => $applicant->car->name_car,
                    'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->user->path : null,
                    'purpose' => $applicant->purpose,
                    'submission_date' => $applicant->submission_date,
                    'expiry_date' => $applicant->expiry_date,
                    'status_admin' => $adminApprovals->get(Auth::id(), ['approval_status' => 'Selesai'])['approval_status'],
                    'status' => $applicant->status,
                    'notes' => $adminApprovals->get(Auth::id(), ['notes' => 'Tidak Ada'])['notes'],
                    'admin_approvals' => $adminApprovals
                ];
            });
    
            // Cek apakah user meminta untuk export Excel
            if ($request->input('export') == 'excel') {
                return Excel::download(new ApplicantsExport($applicants), 'applicants.xlsx');
            }
    
            return response()->json([
                'car' => $datacar,
                'dataApplicant' => $applicants,
                'total_page' => $totalpage,
            ], 200);
        } else {
            return response()->json([
                "message" => "Your Login Not Admin"
            ]);
        }
    }
    





    public function exportApplicants(Request $request)
    {
        $applicantQuery = Applicant::with('user', 'car');

        if ($request->has('status')) {
            $status = $request->input('status');
            if (is_array($status)) {
                $applicantQuery->whereIn('status', $status);
            } else {
                $applicantQuery->where('status', $status);
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $applicantQuery->whereBetween('submission_date', [$startDate, $endDate]);
        }

        $applicants = $applicantQuery->get()->transform(function ($applicant) {
            return [
                'id' => $applicant->id,
                'user_id' => $applicant->user_id,
                'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                'email' => $applicant->user->email,
                'car_id' => $applicant->car_id,
                'car_name' =>    $applicant->car->name_car ,
                'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles' . $applicant->user->path : null,
                'purpose' => $applicant->purpose,
                'submission_date' => $applicant->submission_date,
                'expiry_date' => $applicant->expiry_date,
                'status' => $applicant->status,
                'notes' => $applicant->notes,
            ];
        });

        return Excel::download(new ApplicantsExport($applicants), 'applicants.xlsx');
    }


    public function detailApplicant($id)
    {
        if (!Auth::check()) {
            return response()->json([
                "message" => "User not authenticated"
            ], 401); // Status 401 untuk unauthorized
        }
    
        if (Auth::user()->role->id == 1) {
            $applicant = Applicant::with('user', 'car', 'adminApplicantApprovals')->find($id);
    
            // Periksa apakah pelamar ditemukan
            if (!$applicant) {
                return response()->json([
                    'message' => "Applicant Not Found"
                ], 404);
            }
    
            // Ambil data mobil yang terkait dengan pelamar
            $car = $applicant->car;
    
            // Format data pelamar
            $dataApplicant = [
                'id' => $applicant->id,
                'user_id' => $applicant->user_id,
                'name' => $applicant->user->FirstName . ' ' . $applicant->user->LastName,
                'email' => $applicant->user->email,
                'car' => [
                    'id' => $car->id,
                    'name' => $car->name_car,
                    'status_name' => $car->status,
                    'path' => $car->path ? env('APP_URL') . 'uploads/profiles/' . $car->path : null,
                ],
                'path' => $applicant->user->path ? env('APP_URL') . 'uploads/profiles/' . $applicant->user->path : null,
                'purpose' => $applicant->purpose,
                'submission_date' => $applicant->submission_date,
                'expiry_date' => $applicant->expiry_date,
                'status' => $applicant->status,
                'status_admin' => $applicant->adminApplicantApprovals->pluck('approval_status')->get(Auth::id(), 'Selesai'),
                'notes' => $applicant->adminApplicantApprovals->pluck('notes')->get(Auth::id(), 'Tidak Ada'),
                'admin_approvals' => $applicant->adminApplicantApprovals->mapWithKeys(function ($approval) {
                    return  [
                            'id' => $approval->id,
                                'user_id' => $approval->user_id,
                                'approval_status' => $approval->approval_status,
                                'notes' => $approval->notes,
                    ];
                }),
            ];
    
            return response()->json([
                'dataApplicant' => $dataApplicant
            ], 200);
        } else {
            return response()->json([
                "message" => "Your Login Not Admin"
            ], 403); // Status 403 untuk forbidden
        }
    }
    
public function accepted($applicant_id)
{
    // Ambil user_id dari admin yang sedang login
    $userId = Auth::user()->id; // Bisa juga pakai auth()->id()

    // Dapatkan record applicant
    $applicant = Applicant::findOrFail($applicant_id);

    // Cek apakah admin ini bertanggung jawab atas mobil yang diminta
    $isAdminOfCar = AdminCar::where('car_id', $applicant->car_id)
                            ->where('user_id', $userId)
                            ->exists();

    if (!$isAdminOfCar) {
        return response()->json(['message' => 'Anda tidak memiliki hak untuk menyetujui aplikasi ini karena Anda bukan admin mobil ini.'], 403);
    }

    // Dapatkan record approval yang sesuai berdasarkan admin yang login
    $approval = AdminApplicantApproval::where('user_id', $userId)
        ->where('applicant_id', $applicant_id)
        ->first();

    if (!$approval) {
        return response()->json(['message' => 'Anda tidak memiliki hak untuk menyetujui aplikasi ini.'], 403);
    }

    // Cek apakah admin ini sudah memberikan keputusan sebelumnya
    if ($approval->approval_status !== 'Pending') {
        return response()->json(['message' => 'Anda sudah memberikan keputusan untuk aplikasi ini.'], 403);
    }

    // Update status approval dari admin
    $approval->update([
        'approval_status' => 'Approved',
    ]);

    // Cek apakah semua admin sudah menyetujui atau ada yang menolak
    $pendingApprovals = AdminApplicantApproval::where('applicant_id', $applicant_id)
        ->where('approval_status', 'Pending')
        ->count();

    if ($pendingApprovals === 0) {
        // Semua admin sudah memberikan keputusan (Approved/Rejected)

        // Cek apakah ada admin yang menolak
        $rejectedCount = AdminApplicantApproval::where('applicant_id', $applicant_id)
            ->where('approval_status', 'Rejected')
            ->count();

        if ($rejectedCount > 0) {
            // Set status applicant ke 'Ditolak' jika ada yang menolak
            Applicant::where('id', $applicant_id)
                ->update(['status' => 'Ditolak']);
        } else {
            // Jika semua admin setuju, set status applicant ke 'Disetujui'
            $applicant->update([
                'status' => 'Disetujui',
                'accepted_at' => Carbon::now()
            ]);

            // Update status mobil di tabel 'cars' menjadi 'In Use'
            $applicant->car()->update(['status' => 'In Use']);

            // Otomatis tolak pengajuan lain di hari yang sama untuk mobil yang sama
            $otherApplicants = Applicant::where('car_id', $applicant->car_id)
                ->where('id', '!=', $applicant->id) // Pengajuan selain yang disetujui
                ->where('submission_date', $applicant->submission_date) // Pada tanggal yang sama
                ->where('status', 'Belum Disetujui')
                ->get();

            foreach ($otherApplicants as $otherApplicant) {
                // Update status applicant jadi 'Ditolak'
                $otherApplicant->update([
                    'status' => 'Ditolak',
                ]);

                // Update status approval untuk setiap admin yang bertanggung jawab jadi 'Rejected'
                AdminApplicantApproval::where('applicant_id', $otherApplicant->id)
                    ->where('approval_status', 'Pending')
                    ->update([
                        'approval_status' => 'Rejected',
                        'notes' => 'Maaf Mobil Sudah Dipinjam User Lain'
                ]);
            }
        }
    }

    return response()->json(['message' => 'Aplikasi berhasil disetujui!'], 200);
}

public function denied(Request $request, $applicant_id)
{
    // Ambil user_id dari admin yang sedang login
    $userId = Auth::user()->id;

    // Validasi input request, notes wajib diisi
    $request->validate([
        'notes' => 'required|string',
    ]);

    // Dapatkan record applicant
    $applicant = Applicant::findOrFail($applicant_id);

    // Cek apakah admin ini bertanggung jawab atas mobil yang diminta
    $isAdminOfCar = AdminCar::where('car_id', $applicant->car_id)
                            ->where('user_id', $userId)
                            ->exists();

    if (!$isAdminOfCar) {
        return response()->json(['message' => 'Anda tidak memiliki hak untuk menolak aplikasi ini karena Anda bukan admin mobil ini.'], 403);
    }

    // Dapatkan record approval yang sesuai berdasarkan admin yang login
    $approval = AdminApplicantApproval::where('user_id', $userId)
        ->where('applicant_id', $applicant_id)
        ->first();

    if (!$approval) {
        return response()->json(['message' => 'Anda tidak memiliki hak untuk menolak aplikasi ini.'], 403);
    }

    // Cek apakah admin ini sudah memberikan keputusan sebelumnya
    if ($approval->approval_status !== 'Pending') {
        return response()->json(['message' => 'Anda sudah memberikan keputusan untuk aplikasi ini dan tidak bisa mengubahnya lagi.'], 403);
    }

    // Update status approval dari admin, termasuk catatan (notes)
    $approval->update([
        'approval_status' => 'Rejected',
        'notes' => $request->input('notes'),
    ]);

    // Update status applicant menjadi 'Ditolak' jika belum ditolak
    if ($applicant->status !== 'Ditolak') {
        $applicant->update(['status' => 'Ditolak']);
    }

    return response()->json(['message' => 'Aplikasi berhasil ditolak!'], 200);
}

}