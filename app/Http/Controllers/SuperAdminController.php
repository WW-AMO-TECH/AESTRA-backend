<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class SuperAdminController extends Controller
{
    // GET ALL ADMINS (PENDING + APPROVED + REJECTED)
    public function getAdmins()
    {
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json($admins);
    }

    // GET ALL PENDING ADMIN SIGNUP REQUESTS
    public function adminRequests()
    {
        $pendingAdmins = User::where('role', 'admin')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($pendingAdmins);
    }

    // APPROVE ADMIN SIGNUP REQUEST
    public function approveAdmin($id) {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();

        return response()->json(['message' => 'Admin approved']);
    }

    // REJECT & DELETE ADMIN SIGNUP REQUEST
    public function rejectAdmin($id) {
        $user = User::findOrFail($id);
        $user->status = 'rejected';
        $user->delete();

        return response()->json(['message' => 'Admin rejected']);
    }

    // GET ALL USERS
    public function users()
    {
        
        $users = User::where('role', 'user')
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json($users);
    }


    
    // BLOCK USER OR ADMIN
    public function blockUser($id)
    {
        $user = User::findOrFail($id);

        $user->is_blocked = true;
        $user->save();

        return response()->json(['message' => 'User blocked']);
    }

    // UNBLOCK USER OR ADMIN
    public function unblockUser($id)
    {
        $user = User::findOrFail($id);

        $user->is_blocked = false;
        $user->save();

        return response()->json(['message' => 'User unblocked']);
    }
}