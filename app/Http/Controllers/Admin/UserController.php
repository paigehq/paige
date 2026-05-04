<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Actions\DeactivateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected readonly DeactivateUser $deactivateUser)
    {
        //
    }

    public function index(Request $request): Response
    {
        $sort = in_array($request->input('sort'), ['name', 'email', 'last_active_at', 'space_count'], true)
            ? $request->input('sort')
            : 'name';
        $dir = $request->input('dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $users = User::query()
            ->select('users.*')
            ->addSelect([
                'space_count' => Permission::selectRaw('count(distinct space_id)')
                    ->whereColumn('subject_id', 'users.id')
                    ->where('subject_type', User::class),
            ])
            ->with('roles')
            ->when($request->input('search'),
                fn ($q, $search) => $q->where(fn ($q) => $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                )
            )
            ->orderBy($sort, $dir)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'plan' => $u->plan,
                'role' => $u->roles->first()?->name,
                'space_count' => $u->space_count,
                'last_active_at' => $u->last_active_at?->toDateTimeString(),
                'deactivated_at' => $u->deactivated_at?->toDateTimeString(),
            ]);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'sort', 'dir']),
        ]);
    }

    public function show(User $user): Response
    {
        $user->load('roles');

        $spaces = Permission::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->with('space')
            ->get()
            ->map(fn (Permission $p) => [
                'id' => $p->space?->id,
                'name' => $p->space?->name,
                'slug' => $p->space?->slug,
                'action' => $p->action->value,
            ])
            ->filter(fn ($s) => $s['id'] !== null)
            ->values();

        return Inertia::render('admin/users/Detail', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan' => $user->plan,
                'role' => $user->roles->first()?->name,
                'last_active_at' => $user->last_active_at?->toDateTimeString(),
                'deactivated_at' => $user->deactivated_at?->toDateTimeString(),
                'created_at' => $user->created_at->toDateTimeString(),
            ],
            'spaces' => $spaces,
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->syncRoles([$request->validated('role')]);

        return redirect("/admin/users/$user->id");
    }

    /**
     * @throws Throwable
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            abort(403, 'You cannot deactivate your own account.');
        }

        $this->deactivateUser->handle($user);

        return redirect('/admin/users');
    }
}
