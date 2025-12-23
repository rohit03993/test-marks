<?php

namespace App\Http\Controllers;

use App\Models\AcademicClass;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassController extends Controller
{
    /**
     * Display list of classes
     */
    public function index(Request $request)
    {
        $classes = AcademicClass::query()
            ->withCount(['activeStudents', 'exams'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20);

        return Inertia::render('Classes/Index', [
            'classes' => $classes,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show create class form
     */
    public function create()
    {
        return Inertia::render('Classes/Create');
    }

    /**
     * Store new class
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classes,name',
        ]);

        AcademicClass::create([
            'name' => $request->name,
        ]);

        return redirect()->route('classes.index')
            ->with('success', 'Class created successfully.');
    }

    /**
     * Show edit class form
     */
    public function edit(AcademicClass $class)
    {
        return Inertia::render('Classes/Create', [
            'classItem' => $class,
        ]);
    }

    /**
     * Update class
     */
    public function update(Request $request, AcademicClass $class)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:classes,name,' . $class->id,
        ]);

        $class->update([
            'name' => $request->name,
        ]);

        return redirect()->route('classes.index')
            ->with('success', 'Class updated successfully.');
    }

    /**
     * Delete class
     */
    public function destroy(AcademicClass $class)
    {
        // Check if class has students or exams
        if ($class->classStudents()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete class with assigned students.']);
        }

        if ($class->exams()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete class with exams.']);
        }

        $class->delete();

        return redirect()->route('classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}
