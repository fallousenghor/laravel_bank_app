<?php

namespace App\Interfaces;

interface TransactionRepositoryInterface
{
    public function getAllTransactions();
    public function getTransactionById($transactionId);
    public function createTransaction(array $transactionDetails);
    public function updateTransaction($transactionId, array $transactionDetails);
    public function deleteTransaction($transactionId);
    public function getTransactionsByCompteId($compteId);
    public function getTransactionsByUserId($userId);
}
