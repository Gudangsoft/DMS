<?php

namespace App\Policies;

use App\Models\DocumentComment;
use App\Models\User;

class DocumentCommentPolicy
{
    public function create(User $user, DocumentComment $comment): bool
    {
        return app(DocumentPolicy::class)->comment($user, $comment->document);
    }

    public function delete(User $user, DocumentComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
