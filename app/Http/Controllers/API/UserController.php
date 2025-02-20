<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
 
    public function updateRole(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $user = User::findOrFail($id);

            $currentUser = $request->user();
            if (!$currentUser) {
                throw new \Exception('Authenticated user not found.');
            }

            if ($currentUser->id === $user->id) {
                throw new AuthorizationException('Tidak boleh mengubah role sendiri');
            }

            $this->authorize('updateRole', $user);

            $validated = $request->validate([
                'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])]
            ]);

            $user->update(['role' => $validated['role']]);
            $user->refresh();

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Role updated successfully',
                'updated_user' => $user->only('id', 'name', 'email', 'role'),
                'metadata'     => [
                    'updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
                    'changed_by' => $currentUser->only('id', 'name'),
                ],
            ], Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return $this->errorResponse(
                'User not found',
                ['id' => ['User tidak ditemukan']],
                Response::HTTP_NOT_FOUND
            );
        } catch (AuthorizationException $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Unauthorized action',
                ['authorization' => [$e->getMessage()]],
                Response::HTTP_FORBIDDEN
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating role: " . $e->getMessage());
            return $this->errorResponse(
                'Failed to update role',
                ['server' => ['An unexpected error occurred']],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function index(Request $request)
    {
        try {
            $this->authorize('viewAny', User::class);

            $users = User::select('id', 'name', 'email', 'role', 'email_verified_at')
                        ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => $users->isEmpty() ? 'No users found' : 'Users retrieved successfully',
                'data'    => $users->items(),
                'meta'    => [
                    'current_page' => $users->currentPage(),
                    'per_page'     => $users->perPage(),
                    'total'        => $users->total(),
                    'last_page'    => $users->lastPage(),
                ],
            ], Response::HTTP_OK);
        } catch (AuthorizationException $e) {
            return $this->errorResponse(
                'Unauthorized access',
                ['authorization' => [$e->getMessage()]],
                Response::HTTP_FORBIDDEN
            );
        } catch (\Exception $e) {
            Log::error("Error retrieving users: " . $e->getMessage());
            return $this->errorResponse(
                'Failed to retrieve users',
                ['server' => ['An unexpected error occurred']],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Helper method to generate standardized error responses.
     *
     * @param  string  $message
     * @param  array  $errors
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($message, $errors, $statusCode)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }
}