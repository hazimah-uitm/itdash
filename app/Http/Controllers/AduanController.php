<?php

namespace App\Http\Controllers;

use App\Models\Aduan;
use Illuminate\Http\Request;

class AduanController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 10);
        $type = $request->input('type');
        $attendance = $request->input('attendance');
        $status = $request->input('status');

        $query = Aduan::query();

        if ($type) {
            $query->where('type', $type);
        }

        if ($attendance) {
            $query->where('attendance', $attendance);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $aduanList = $query->orderBy('name', 'asc')->paginate($perPage);

        return view('pages.aduan.index', [
            'staffList' => $aduanList,
            'perPage' => $perPage,
            'type' => $type,
            'attendance' => $attendance,
            'status' => $status,
        ]);
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        try {
            Excel::import(new StaffImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data telah berjaya di import');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        return Excel::download(new StaffExport(
            $request->input('type'),
            $request->input('attendance'),
            $request->input('status')
        ), 'Rekod-RSVP-Malam-Gala.xlsx');
    }

    public function create()
    {
        return view('pages.aduan.create', [
            'save_route' => route('aduan.store'),
            'str_mode' => 'Tambah',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'nullable',
            'name' => 'required',
            'no_pekerja' => 'required',
            'attendance' => 'required|in:Hadir,Tidak Hadir',
            'category' => 'nullable|in:Staf Akademik,Staf Pentadbiran',
            'department' => 'nullable',
            'campus' => 'nullable',
            'club' => 'nullable|in:Ahli KEKiTA,Ahli PEWANI,Bukan Ahli  (Bayaran RM20 dikenakan)',
            'payment' => 'nullable',
            'type' => 'required|in:Staf,Bukan Staf',
            'status' => 'required|in:Belum Tempah,Selesai Tempah',
        ], [
            'name.required' => 'Sila isi Nama',
            'no_pekerja.required' => 'Sila isi No. Pekerja',
            'attendance.required' => 'Sila sahkan kehadiran',
            'type.required' => 'Sila isi jenis pengguna',
            'status.required' => 'Sila pilih Status',
        ]);

        $existingStaff = Aduan::where('no_pekerja', strtoupper($request->input('no_pekerja')))
            ->whereNull('deleted_at') 
            ->first();

        if ($existingStaff) {
            return redirect()->back()->withErrors(['no_pekerja' => 'No. Pekerja telah wujud'])->withInput();
        }

        $aduan = new Aduan();

        $aduan->fill($request->except(['name', 'no_pekerja']));
        $aduan->name = strtoupper($request->input('name'));
        $aduan->no_pekerja = strtoupper($request->input('no_pekerja'));
        $aduan->save();

        return redirect()->route('staff')->with('success', 'Maklumat berjaya disimpan');
    }

    public function show($id)
    {
        $aduan = Aduan::findOrFail($id);

        return view('pages.aduan.view', [
            'staff' => $aduan,
        ]);
    }

    public function edit($id)
    {
        $aduan = Aduan::findOrFail($id);

        return view('pages.aduan.edit', [
            'staff' => $aduan,
            'save_route' => route('aduan.update', $id),
            'str_mode' => 'Kemaskini',
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'email' => 'nullable',
            'name' => 'required',
            'no_pekerja' => 'required',
            'attendance' => 'required|in:Hadir,Tidak Hadir',
            'category' => 'nullable|in:Staf Akademik,Staf Pentadbiran',
            'department' => 'nullable',
            'campus' => 'nullable',
            'club' => 'nullable|in:Ahli KEKiTA,Ahli PEWANI,Bukan Ahli  (Bayaran RM20 dikenakan)',
            'payment' => 'nullable',
            'type' => 'required|in:Staf,Bukan Staf',
            'status' => 'required|in:Belum Tempah,Selesai Tempah',
        ], [
            'name.required' => 'Sila isi Nama',
            'no_pekerja.required' => 'Sila isi No. Pekerja',
            'attendance.required' => 'Sila sahkan kehadiran',
            'type.required' => 'Sila isi jenis pengguna',
            'status.required' => 'Sila pilih Status',
        ]);

        // Check if a staff with the same no_pekerja exists (excluding the current record)
        $existingStaff = Aduan::whereNull('deleted_at') 
            ->where('no_pekerja', strtoupper($request->input('no_pekerja')))
            ->where('id', '<>', $id)
            ->first();

        if ($existingStaff) {
            // If the staff exists, handle the logic accordingly
            return redirect()->back()->with('error', 'No. Pekerja telah wujud');
        }

        // Find the staff record by ID
        $aduan = Aduan::findOrFail($id);

        $aduan->fill($request->except(['name', 'no_pekerja']));
        $aduan->name = strtoupper($request->input('name'));
        $aduan->no_pekerja = strtoupper($request->input('no_pekerja'));
        $aduan->save();

        return redirect()->route('staff')->with('success', 'Maklumat berjaya dikemas kini');
    }


    public function search(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->input('perPage', 10);
        $type = $request->input('type');
        $attendance = $request->input('attendance');
        $status = $request->input('status');

        $query = Aduan::query();

        if ($search) {
            $query->where('name', 'LIKE', "%$search%")
                ->orWhere('no_pekerja', 'LIKE', "%$search%")
                ->orWhere('attendance', 'LIKE', "%$search%")
                ->orWhere('status', 'LIKE', "%$search%")
                ->orWhere('type', 'LIKE', "%$search%")
                ->orWhere('campus', 'LIKE', "%$search%");
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($attendance) {
            $query->where('attendance', $attendance);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $aduanList = $query->latest()->paginate($perPage);

        return view('pages.aduan.index', [
            'staffList' => $aduanList,
            'perPage' => $perPage,
            'search' => $search,
            'type' => $type,
            'attendance' => $attendance,
            'status' => $status,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $aduan = Aduan::findOrFail($id);

        $aduan->delete();

        return redirect()->route('staff')->with('success', 'Maklumat berjaya dihapuskan');
    }

    public function trashList()
    {
        $trashList = Aduan::onlyTrashed()->latest()->paginate(10);

        return view('pages.aduan.trash', [
            'trashList' => $trashList,
        ]);
    }

    public function restore($id)
    {
        Aduan::withTrashed()->where('id', $id)->restore();

        return redirect()->route('aduan')->with('success', 'Maklumat berjaya dikembalikan');
    }


    public function forceDelete($id)
    {
        $aduan = Aduan::withTrashed()->findOrFail($id);

        $aduan->forceDelete();

        return redirect()->route('aduan.trash')->with('success', 'Maklumat berjaya dihapuskan sepenuhnya');
    }
}