<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\ListTransactionsRequest;
use Illuminate\Http\Request;
use App\Interfaces\TransactionRepositoryInterface;
use App\Traits\ApiResponse;

class TransactionController extends Controller
{
    use ApiResponse;
    private $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function index(ListTransactionsRequest $request)
    {
        $transactions = $this->transactionRepository->getAllTransactions();
        // Note: Vous pourriez ajouter des méthodes spécifiques dans le repository pour gérer ces filtres
        return $this->successResponse(TransactionResource::collection($transactions), 'Transactions récupérées');
    }

    public function show(Request $request, $id = null)
    {
        $id = $id ?? $request->route('id') ?? $request->query('id');
        $transaction = $this->transactionRepository->getTransactionById($id);
        return $this->successResponse(new TransactionResource($transaction), 'Détails de la transaction');
    }
}
