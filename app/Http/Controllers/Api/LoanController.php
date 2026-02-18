<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Services\LoanService;
use App\Exceptions\BookNotAvailableException;
use App\Exceptions\BookReservedException;
use App\Exceptions\InvalidLoanStateException;
use App\Exceptions\MaxLoansExceededException;
use App\Exceptions\UnpaidFineException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function __construct(private LoanService $loanService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $loans = $request->user()
            ->loans()
            ->with('book')
            ->get();

        return response()->json(['data' => $loans]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $book = Book::findOrFail($request->book_id);

        try {
            $loan = $this->loanService->borrowBook($request->user(), $book);
        } catch (BookNotAvailableException|MaxLoansExceededException|UnpaidFineException|BookReservedException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['data' => $loan], 201);
    }

    public function return(Request $request, int $id): JsonResponse
    {
        $loan = Loan::findOrFail($id);

        try {
            $returnedLoan = $this->loanService->returnBook($loan);
        } catch (InvalidLoanStateException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['data' => $returnedLoan]);
    }
}
