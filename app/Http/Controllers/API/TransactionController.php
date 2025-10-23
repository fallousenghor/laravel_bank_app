<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\ListTransactionsRequest;
use App\Http\Requests\ShowTransactionRequest;

class TransactionController extends Controller
{
    public function index(ListTransactionsRequest $request)
    {
        $query = Transaction::with('compte');

        if ($request->has('compte_id')) {
            $query->where('compte_id', $request->compte_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_debut')) {
            $query->where('date', '>=', $request->date_debut);
        }

        if ($request->has('date_fin')) {
            $query->where('date', '<=', $request->date_fin);
        }

        if ($request->has('montant_min')) {
            $query->where('montant', '>=', $request->montant_min);
        }

        if ($request->has('montant_max')) {
            $query->where('montant', '<=', $request->montant_max);
        }

        $transactions = $query->get();
        return TransactionResource::collection($transactions);
    }

    public function show(ShowTransactionRequest $request)
    {
        $id = $request->validated()['id'];
        $transaction = Transaction::with('compte')->findOrFail($id);
        return new TransactionResource($transaction);
    }
}
