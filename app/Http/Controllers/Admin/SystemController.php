<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class SystemController extends Controller
{
    public function users()
    {
        return Inertia::render('Admin/Users', [
            'users' => User::with(['roles:id,name', 'branch:id,name'])->orderBy('name')->get()->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                'role' => $u->roles->first()?->name, 'branch' => $u->branch?->name, 'branch_id' => $u->branch_id,
                'status' => $u->status,
            ]),
            'roles' => Role::orderBy('name')->pluck('name'),
            'branches' => Branch::get(['id', 'name']),
        ]);
    }

    public function userSave(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email'.($request->id ? ",{$request->id}" : ''),
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|exists:roles,name',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => 'required|in:active,inactive',
            'password' => ($request->id ? 'nullable' : 'required').'|string|min:8',
        ]);

        if (! empty($data['id'])) {
            $user = User::find($data['id']);
            $user->update([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'branch_id' => $data['branch_id'] ?? null, 'status' => $data['status'],
            ] + (! empty($data['password']) ? ['password' => Hash::make($data['password'])] : []));
        } else {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'] ?? null,
                'branch_id' => $data['branch_id'] ?? null, 'status' => $data['status'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
            ]);
        }
        $user->syncRoles([$data['role']]);

        return back()->with('success', 'Staff account saved.');
    }

    public function settings()
    {
        return Inertia::render('Admin/Settings', [
            'settings' => Setting::pluck('value', 'key'),
            'branches' => Branch::with('warehouses')->get(),
        ]);
    }

    public function settingsSave(Request $request)
    {
        $data = $request->validate(['settings' => 'required|array']);
        foreach ($data['settings'] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return back()->with('success', 'Settings saved.');
    }

    public function audit(Request $request)
    {
        $query = AuditLog::with('user:id,name')->latest('created_at');
        if ($request->filled('action')) {
            $query->where('action', 'ilike', "%{$request->action}%");
        }

        return Inertia::render('Admin/Audit', [
            'logs' => $query->paginate(25)->withQueryString()->through(fn ($l) => [
                'id' => $l->id, 'user' => $l->user?->name ?? 'System', 'action' => $l->action,
                'subject' => $l->subject_type ? class_basename($l->subject_type).'#'.$l->subject_id : null,
                'changes' => $l->changes, 'ip' => $l->ip, 'at' => $l->created_at->format('d M Y H:i:s'),
            ]),
            'filters' => $request->only(['action']),
        ]);
    }

    public function guide()
    {
        return Inertia::render('Admin/Guide');
    }
}
