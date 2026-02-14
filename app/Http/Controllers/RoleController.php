<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RoleRequest;
use App\Http\Resources\Role as ResourcesRole;
use App\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $roles = [];
        if (isset($request->search)) {
            $roles = Role::where('name', 'like', '%' . $request->search . '%');
        }

        $roles = isset($request->search) && $request->search ? $roles->paginate(10) : Role::paginate(10);
        return ResourcesRole::collection($roles);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        try {
            $role = new Role();
            $role->name = ucwords($request->name);
            $role->slug = Str::slug($request->name,  '-');
            $role->save();

            return response()->json(['message' => 'Role has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update($id, RoleRequest $request)
    {
        try {
            $role = Role::findOrFail($id);
            $role->name = ucwords($request->name);
            $role->slug = Str::slug($request->name,  '-');
            $role->update();
            return response(['message' => 'Role has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Role::findOrFail($id)->delete();
        return response(['message' => 'Role has been successfully deleted!']);
    }
}
