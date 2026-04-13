<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\User as ResourcesUser;
use App\Models\User;
use App\Models\Staff;
use App\Services\UserLogService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use App\Events\UserCreated;

class UserController extends Controller
{

    
    protected $logService;

    public function __construct(UserLogService $logService)
    {
        $this->logService = $logService;
    }


    public function index(Request $request)
    {
        $users = User::when($request->search, function ($db, $search) {
            $db->where(function($q) use($search){
                return $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('middle_name', 'like', '%' . $search . '%');
            });
        })->when($request->role, function ($db, $role) {
            $db->whereHas('roles', function($q) use($role) {
                return $q->where('name', 'like', '%' . $role . '%');
            });
        });

        return ResourcesUser::collection($users->paginate(10));
    }

   public function store(UserRequest $request)
    {
        try {

            $existingUser = User::where('username', $request->username)->whereNull('deleted_at')->first();
            if ($existingUser) {
                return response()->json(['message' => 'Username already exists. Please choose another one.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }


            $default_password = "*1234#";

            $user = new User();
            $user->username = $request->username;
            $user->first_name = ucwords($request->first_name);
            $user->middle_name = ucwords($request->middle_name);
            $user->last_name = ucwords($request->last_name);
            $user->password = bcrypt($default_password);
            $user->status = 'pending';
            $user->save();

            event(new UserCreated($user));

            $this->logService->logAction('User', $user->id, 'create', $user->toArray());
            
            $this->storeUserRoles($user->id, $request->user_roles);

            return response()->json(['message' => 'User has been successfully saved.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function register(UserRequest $request)
{
    try {

        $existingUser = User::where('username', $request->username)->whereNull('deleted_at')->first();
        if ($existingUser) {
            return response()->json(['message' => 'Username already exists. Please choose another one.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = new User();
        $user->username = $request->username;
        $user->first_name = ucwords($request->first_name);
        $user->middle_name = ucwords($request->middle_name);
        $user->last_name = ucwords($request->last_name);
        $user->password = Hash::make($request->password);
        $user->save();

        
        $roles = $request->user_roles ?: ['user']; 
        $this->storeUserRoles($user->id, $roles);

        return response()->json(['message' => 'User has been successfully registered.'], Response::HTTP_CREATED);
    } catch (\Exception $e) {
        return response()->json(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}



    public function storeUserRoles($user_id, $user_roles)
    {
        try {
            $user = User::findOrFail($user_id);
            $oldRoles = $user->roles()->pluck('id')->toArray(); 
            $user->roles()->sync($user_roles);
            $user->update();

            $this->logService->logAction('User', $user->id, 'update_roles', [
                'old_roles' => $oldRoles,
                'new_roles' => $user_roles,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
    }

    public function update($id, UserRequest $request)
    {
        try {
            $user = User::findOrFail($id);
    
            // Check if username is being changed and if new username already exists
            if ($user->username !== $request->username) {
                $existingUser = User::where('username', $request->username)->where('id', '!=', $id)->first();
                if ($existingUser) {
                    return response()->json(['message' => 'Username already exists. Please choose another one.'], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
    
            $oldData = $user->toArray();
            
            // Update user data
            $user->username = $request->username;
            $user->first_name = ucwords($request->first_name);
            $user->middle_name = ucwords($request->middle_name);
            $user->last_name = ucwords($request->last_name);
            $user->status = $request->status;
            $this->storeUserRoles($user->id, $request->user_roles);
            $user->update();
    
           
            
            $this->logService->logAction('User', $user->id, 'update', [
                'old' => $oldData,
                'new' => $user->toArray(),
            ]);
    
            return response(['message' => 'User has been successfully updated.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function resetPassword($id){
        try {
            $default_password = "*1234#";
    
            $user = User::findOrFail($id);
            $user->password = bcrypt($default_password);
            $user->password_reset = true; // Set password_reset to true
            $user->update();
    
            return response(['message' => 'Password has been successfully reset.']);
            
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function manualResetPassword($id, Request $request){
        try {
            $request->validate([
                'password' => 'required|min:6|confirmed', // if using password + password_confirmation
            ]);
    
            $user = User::findOrFail($id);
            $user->password = Hash::make($request->password);
            $user->password_reset = false;
            $user->update();
    
            return response(['message' => 'Password has been successfully reset.']);
        } catch (\Exception $e) {
            return response(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
    
    

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $username = $user->username;

             $staff = Staff::where('user_id', $user->id)->first();
            if ($staff) {
                $staff->delete();
            }
    
            $user->delete();
    
            $this->logService->logAction('User', $id, 'delete');

    
            return response(['message' => 'User has been successfully deleted!']);
        } catch (\Exception $e) {
            \Log::error('Error in UserController@destroy: ' . $e->getMessage());
            return response(['message' => 'Something went wrong.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}
