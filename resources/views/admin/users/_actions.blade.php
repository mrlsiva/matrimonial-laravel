<div class="d-inline-flex gap-1">
    @if($user->profile && $user->profile->approval_status !== 'approved')
        <form method="POST" action="{{ route('admin.users.status', $user) }}">@csrf @method('PATCH')
            <input type="hidden" name="action" value="approve">
            <button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i><span class="d-none d-xl-inline"> Approve</span></button>
        </form>
    @endif
    @if($user->status === 'active')
        <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('Block this user? They will be logged out immediately.')">@csrf @method('PATCH')
            <input type="hidden" name="action" value="block">
            <button class="btn btn-sm btn-outline-dark" title="Block"><i class="bi bi-slash-circle"></i></button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.users.status', $user) }}">@csrf @method('PATCH')
            <input type="hidden" name="action" value="unblock">
            <button class="btn btn-sm btn-outline-success" title="Unblock"><i class="bi bi-unlock"></i></button>
        </form>
    @endif
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user permanently from the site? Payment records are kept.')">@csrf @method('DELETE')
        <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
    </form>
</div>
