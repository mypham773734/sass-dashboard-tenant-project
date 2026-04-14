<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return view('admin.pages.project.index');
    }


    public function create()
    {
        return view('admin.pages.project.create');
    }

    public function store(Request $request)
    {
        // Validate and store the project
        echo "store project";
    }

    public function show($id)
    {
        // Show a specific project
        echo "show project with id: " . $id;
    }

    public function edit($id)
    {
        // Edit a specific project
        echo "edit project with id: " . $id;
    }

    public function update(Request $request, $id)
    {
        // Validate and update the project
        echo "update project with id: " . $id;
    }

    public function destroy($id)
    {
        // Delete a specific project
        echo "delete project with id: " . $id;
    }
}