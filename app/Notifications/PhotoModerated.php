<?php

namespace App\Notifications;

use App\Models\ProfilePhoto;

class PhotoModerated extends AppNotification
{
    public function __construct(private ProfilePhoto $photo) {}

    protected function title(): string
    {
        return 'Photo '.$this->photo->status;
    }

    protected function message(): string
    {
        return $this->photo->status === 'approved'
            ? 'One of your photos has been approved and is now visible.'
            : 'One of your photos was rejected. Please upload a clear, recent photo of yourself.';
    }

    protected function url(): ?string
    {
        return route('photos.index');
    }

    protected function icon(): string
    {
        return 'bi-image';
    }
}
