<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminStudentController extends Controller
{
    public function index()
    {
        $students = User::where('role', 'student')
            ->paginate(20);

        return view('admin.students.index', compact('students'));
    }
}
