<div class="modal fade" id="rejectModal{{ $modalSuffix ?? '' }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.profiles.reject', $profile) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header"><h5 class="modal-title">Reject {{ $profile->profile_code }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body">
                <label class="form-label small" for="reason{{ $modalSuffix ?? '' }}">Reason (sent to the member)</label>
                <select class="form-select form-select-sm mb-2" onchange="this.nextElementSibling.value = this.value" aria-label="Common reasons">
                    <option value="">Choose a common reason…</option>
                    <option>Incomplete profile details</option>
                    <option>Contact details found in the About section</option>
                    <option>Inappropriate or misleading content</option>
                    <option>Suspected fake or duplicate profile</option>
                </select>
                <textarea name="reason" id="reason{{ $modalSuffix ?? '' }}" class="form-control" rows="3" maxlength="255" required></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject profile</button></div>
        </form>
    </div>
</div>
