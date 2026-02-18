<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Reservation;
use App\Services\ReservationService;
use App\Exceptions\BookAvailableForLoanException;
use App\Exceptions\InvalidReservationStateException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function __construct(private ReservationService $reservationService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $reservations = $request->user()
            ->reservations()
            ->with('book')
            ->get();

        return response()->json(['data' => $reservations]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $book = Book::findOrFail($request->book_id);

        try {
            $reservation = $this->reservationService->reserveBook($request->user(), $book);
        } catch (BookAvailableForLoanException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['data' => $reservation], 201);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $reservation = Reservation::findOrFail($id);

        try {
            $cancelledReservation = $this->reservationService->cancelReservation($reservation);
        } catch (InvalidReservationStateException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json(['data' => $cancelledReservation]);
    }
}
