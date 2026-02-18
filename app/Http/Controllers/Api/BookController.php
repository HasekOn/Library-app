<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\BookResource;
class BookController extends Controller
{
    public function index(): JsonResponse
    {
        $books = Book::all();

        return response()->json(['data' => BookResource::collection($books)]);
    }

    public function show(int $id): JsonResponse
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return response()->json(['data' => new BookResource($book)]);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $book = Book::create([
            'title' => $request->title,
            'author' => $request->author,
            'isbn' => $request->isbn,
            'total_copies' => $request->total_copies,
            'available_copies' => $request->total_copies,
        ]);

        return response()->json(['data' => new BookResource($book)], 201);
    }

    public function update(UpdateBookRequest $request, int $id): JsonResponse
    {
        $book = Book::findOrFail($id);

        $book->update($request->validated());

        return response()->json(['data' => new BookResource($book->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        if (!request()->user() || !request()->user()->isLibrarian()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $book->delete();

        return response()->json(null, 204);
    }
}
