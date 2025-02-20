<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Book;
use Illuminate\Support\Facades\Log;


class BookPolicy
{
    /**
     * Semua role (admin, editor, viewer) boleh lihat list buku.
     */
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'editor', 'viewer']);
    }

    /**
     * Semua role boleh lihat detail buku.
     */
    public function view(User $user, Book $book)
    {
        Log::info('User role: ' . $user->role);
        return in_array($user->role, ['admin', 'editor', 'viewer']);
    }
    
    /**
     * Hanya admin dan editor yang boleh buat buku.
     */
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Hanya admin dan editor yang boleh update buku.
     */
    public function update(User $user, Book $book)
    {
        return in_array($user->role, ['admin', 'editor']);
    }

    /**
     * Hanya admin yang boleh hapus buku.
     */
    public function delete(User $user, Book $book)
    {
        return $user->role === 'admin';
    }
}