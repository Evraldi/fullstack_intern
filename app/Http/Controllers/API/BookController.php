<?php

namespace App\Http\Controllers\API;

use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\Response;

class BookController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        try {
            $validated = $request->validate([
                'search'       => 'sometimes|string',
                'tahun_terbit' => 'sometimes|integer',
                'order'        => 'sometimes|in:asc,desc',
                'per_page'     => 'sometimes|integer|min:1',
            ]);
            
            $query = Book::query();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            }

            if ($request->filled('tahun_terbit')) {
                $query->where('tahun_terbit', $request->tahun_terbit);
            }

            $order = $validated['order'] ?? 'asc';
            $query->orderBy('judul', $order);
            $perPage = $validated['per_page'] ?? 10;
            $books = $query->paginate($perPage);

            if ($books->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No books found',
                    'data'    => [],
                    'meta'    => [
                        'current_page' => $books->currentPage(),
                        'per_page'     => $books->perPage(),
                        'total'        => 0,
                        'last_page'    => 0,
                    ]
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'message' => 'Books retrieved successfully',
                'data'    => $books->items(),
                'meta'    => [
                    'current_page' => $books->currentPage(),
                    'per_page'     => $books->perPage(),
                    'total'        => $books->total(),
                    'last_page'    => $books->lastPage(),
                ]
            ], Response::HTTP_OK);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve books',
                'errors'  => ['server' => 'An unexpected error occurred.'],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'judul'        => 'required|string|max:255',
                'penulis'      => 'required|string|max:255',
                'tahun_terbit' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'deskripsi'    => 'required|string',
            ]);

            $this->authorize('create', Book::class);

            $book = Book::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Book added successfully',
                'data'    => $book,
            ], Response::HTTP_CREATED);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action',
                'errors'  => ['authorization' => [$e->getMessage()]],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create book',
                'errors'  => ['server' => ['An unexpected error occurred.']],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $book = Book::findOrFail($id);

            $this->authorize('view', $book);

            return response()->json([
                'success' => true,
                'message' => 'Book details retrieved successfully',
                'data'    => $book,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'errors'  => ['book' => 'The book with the specified ID does not exist.'],
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'errors'  => ['server' => 'Something went wrong. Please try again later.'],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $book = Book::findOrFail($id);
            $this->authorize('update', $book);

            $validated = $request->validate([
                'judul'        => 'sometimes|string|max:255',
                'penulis'      => 'sometimes|string|max:255',
                'tahun_terbit' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
                'deskripsi'    => 'sometimes|string',
            ]);
        
            $book->update($validated);
        
            return response()->json([
                'success' => true,
                'message' => 'Book updated successfully',
                'data'    => $book,
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'errors'  => ['book' => 'The book with the specified ID does not exist.'],
            ], Response::HTTP_NOT_FOUND);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'errors'  => ['authorization' => $e->getMessage()],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update book',
                'errors'  => ['server' => 'An unexpected error occurred.'],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $book = Book::findOrFail($id);
            $this->authorize('delete', $book);

            $book->delete();

            return response()->json([
                'success' => true,
                'message' => 'Book deleted successfully',
            ], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Book not found',
                'errors'  => ['book' => 'The book with the specified ID does not exist.'],
            ], Response::HTTP_NOT_FOUND);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
                'errors'  => ['authorization' => $e->getMessage()],
            ], Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete book',
                'errors'  => ['server' => 'An unexpected error occurred.'],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}