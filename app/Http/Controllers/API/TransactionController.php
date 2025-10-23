<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;


class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::with('compte')->get();
        return response()->json($transactions);
    }

    public function show($id)
    {
        $transaction = Transaction::with('compte')->findOrFail($id);
        return response()->json($transaction);
    }
}
