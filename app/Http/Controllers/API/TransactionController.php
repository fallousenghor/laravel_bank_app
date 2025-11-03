<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\ListTransactionsRequest;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Interfaces\TransactionRepositoryInterface;
use App\Models\Compte;
use App\Models\Transaction;
use App\Traits\ApiResponse;

class TransactionController extends Controller
{
    use ApiResponse;
    private $transactionRepository;

    /**
    * @OA\Get(
    *     path="/senghorfallou/v1/transactions",
     *     summary="Lister les transactions",
     *     description="Retourne l'historique paginé des transactions du client authentifié.",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
    *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1), description="Numéro de page (défaut: 1)"),
    *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=10), description="Nombre d'éléments par page (défaut: 10, max:100)"),
        *     @OA\Parameter(
        *         name="numero_compte",
        *         in="query",
        *         @OA\Schema(type="string", example="CPT123456"),
        *         description="numéro de compte . Ex: CPT123456"
        *     ),
    *     @OA\Parameter(
    *         name="type",
    *         in="query",
    *         @OA\Schema(
    *             type="string",
    *             enum={"debit","credit","retrait","depot"}
    *         ),
    *         description="Filtrer par type "
    *     ),
    *
    *     @OA\Response(response=200, description="Liste paginée de transactions", @OA\JsonContent(ref="#/components/schemas/TransactionsListResponse"))
     * )
     */

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function index(ListTransactionsRequest $request)
    {
        $user = $request->user();

        // Base query. If the authenticated user is NOT an admin, restrict
        // results to transactions belonging to their comptes. Admins may see all transactions.
        $query = Transaction::query();
        if (!isset($user->role) || $user->role !== 'admin') {
            // Non-admin users only see their own transactions
            $query->whereHas('compte', function ($q) use ($user) {
                $q->where('client_id', $user->id);
            });
        }

        // Apply filters (compte, type, dates, montant)
            if ($request->filled('numero_compte')) {
                // The API parameter `numero_compte` is the account number
                // (stored in `comptes.numero`).
                $numero = $request->input('numero_compte');
                $query->whereHas('compte', function ($q) use ($numero) {
                    $q->where('numero', $numero);
                });
            }

        if ($request->filled('type')) {
            $type = $request->input('type');
            if ($type === 'debit') {
                $query->where('type', 'retrait');
            } elseif ($type === 'credit') {
                $query->where('type', 'depot');
            } else {
                $query->where('type', $type);
            }
        }

        // Note: date_debut removed from public API; other optional filters (date_fin/montant_min/max)
        // were removed from documentation earlier. Server still accepts them if provided but
        // they're intentionally not advertised in Swagger.

        // Search (e.g. by compte numero)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('compte', function ($q) use ($search) {
                $q->where('numero', 'ilike', "%{$search}%");
            });
        }

        // Sorting: map API-friendly sort fields to DB columns
        $sort = $request->input('sort', 'date');
        $order = $request->input('order', 'desc');

        $sortMap = [
            'date' => 'date',
            'montant' => 'montant',
            'type' => 'type',
            'compte' => null, // handled by join
        ];

        // Pagination params (default similar to comptes)
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        // Apply sorting
        if ($sort === 'compte') {
            // join comptes table to sort by numero
            $query->join('comptes', 'transactions.compte_id', '=', 'comptes.id')
                  ->orderBy('comptes.numero', $order)
                  ->select('transactions.*');
        } else {
            $column = $sortMap[$sort] ?? 'date';
            $query->orderBy($column, $order);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform items with TransactionResource
        $collection = $paginator->getCollection()->map(function ($tx) use ($request) {
            return (new TransactionResource($tx))->toArray($request);
        });

        // Replace paginator collection with transformed items
        $paginator->setCollection($collection);

        return $this->paginatedResponse($paginator, 'Transactions récupérées');
    }

    /**
    * @OA\Post(
    *     path="/senghorfallou/v1/transactions",
    *     summary="Créer une transaction (depot, retrait, virement)",
    *     description="Crée une transaction de type depot, retrait ou virement. Les clients peuvent effectuer des virements uniquement entre leurs propres comptes; les administrateurs peuvent opérer sur tous les comptes.",
    *     tags={"Transactions"},
    *     security={{"bearerAuth":{}}},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\JsonContent(
    *             required={"type","montant"},
    *             @OA\Property(property="type", type="string", example="virement", description="Type de transaction: depot, retrait ou virement"),
    *             @OA\Property(property="numero_compte", type="string", description="Numéro de compte pour depot/retrait (ex: CPT123456)"),
    *             @OA\Property(property="source_numero", type="string", description="Numéro du compte source pour virement"),
    *             @OA\Property(property="destination_numero", type="string", description="Numéro du compte destination pour virement"),
    *             @OA\Property(property="montant", type="number", format="float", description="Montant de la transaction"),
    *             @OA\Property(property="libelle", type="string", description="Libellé optionnel")
    *         )
    *     ),
    *     @OA\Response(response=200, description="Transaction créée", @OA\JsonContent(type="object")),
    *     @OA\Response(response=403, description="Accès refusé - compte non autorisé"),
    *     @OA\Response(response=422, description="Erreur de validation / solde insuffisant"),
    *     @OA\Response(response=500, description="Erreur interne du serveur")
    * )
     */

    /**
     * Effectue un virement entre deux comptes appartenant (au moins le compte source appartient) à l'utilisateur.
     */
    public function store(StoreTransactionRequest $request)
    {
        $user = $request->user();

        $type = $request->input('type');
        $amount = $request->input('montant');
        $libelle = $request->input('libelle');

        // Resolve comptes by numero (we use account numbers in the API)
        if ($type === 'virement') {
            $source = Compte::where('numero', $request->input('source_numero'))->firstOrFail();
            $destination = Compte::where('numero', $request->input('destination_numero'))->firstOrFail();
        } else {
            // depot or retrait
            $compte = Compte::where('numero', $request->input('numero_compte'))->firstOrFail();
        }

        // If variables $source/$destination already set above (virement case), keep them.
        if (!isset($source) || !isset($destination)) {
            // For depot/retrait we only have $compte
            if (isset($compte)) {
                $source = $compte;
                $destination = $compte;
            } else {
                // Fallback: try to load by original ids if provided (backwards compat)
                $sourceId = $request->input('source_compte_id');
                $destId = $request->input('destination_compte_id');
                if ($sourceId) $source = Compte::findOrFail($sourceId);
                if ($destId) $destination = Compte::findOrFail($destId);
            }
        }

        // Authorization & basic rules depending on type
        if ($type === 'virement') {
            if (!isset($user->role) || $user->role !== 'admin') {
                if ($source->client_id !== $user->id) {
                    return $this->errorResponse('Compte source non autorisé', 403);
                }
                if ($destination->client_id !== $user->id) {
                    return $this->errorResponse('Vous ne pouvez effectuer des virements que vers vos propres comptes', 403);
                }
            }

            // Prevent transfers to the same account
            if ($source->id === $destination->id) {
                return $this->errorResponse('Le compte source et le compte destination doivent être différents', 422);
            }
        } elseif ($type === 'retrait') {
            // retrait: check owner and balance
            if (!isset($user->role) || $user->role !== 'admin') {
                if ($compte->client_id !== $user->id) {
                    return $this->errorResponse('Compte non autorisé', 403);
                }
            }
            $source = $compte;
        } elseif ($type === 'depot') {
            // depot: check owner for non-admins
            if (!isset($user->role) || $user->role !== 'admin') {
                if ($compte->client_id !== $user->id) {
                    return $this->errorResponse('Compte non autorisé', 403);
                }
            }
            $source = $compte;
        }

        if ($amount <= 0) {
            return $this->errorResponse('Le montant doit être supérieur à zéro.', 422);
        }

        // Perform atomic transaction and check balance while locking rows to avoid
        // concurrent updates or relying on model accessors that run separate queries
        try {
            $result = DB::transaction(function () use ($source, $destination, $amount, $request, &$debitTx, &$creditTx) {
                // Reload and lock the comptes for update to compute balance safely
                $lockedSource = Compte::where('id', $source->id)->lockForUpdate()->first();
                $lockedDestination = Compte::where('id', $destination->id)->lockForUpdate()->first();

                // Calculate source's current balance from transactions inside the transaction
                $calculatedSolde = (float) Transaction::where('compte_id', $lockedSource->id)
                    ->selectRaw("COALESCE(SUM(CASE WHEN type = 'depot' THEN montant WHEN type = 'retrait' THEN -montant ELSE 0 END),0) as s")
                    ->value('s');

                if ($calculatedSolde < $amount) {
                    // Throw to trigger rollback and be caught below
                    throw new \Exception('Solde insuffisant');
                }

                // Create debit transaction on source
                $debitTx = $this->transactionRepository->createTransaction([
                    'compte_id' => $lockedSource->id,
                    'montant' => $amount,
                    'type' => 'retrait',
                    'date' => now(),
                ]);

                // Create credit transaction on destination
                $creditTx = $this->transactionRepository->createTransaction([
                    'compte_id' => $lockedDestination->id,
                    'montant' => $amount,
                    'type' => 'depot',
                    'date' => now(),
                ]);

                // Update stored balances using decrement/increment to avoid race
                $lockedSource->decrement('solde', $amount);
                $lockedDestination->increment('solde', $amount);

                return compact('debitTx', 'creditTx');
            });
        } catch (\Exception $e) {
            \Log::error('Erreur lors du virement: ' . $e->getMessage());
            if ($e->getMessage() === 'Solde insuffisant') {
                return $this->errorResponse('Solde insuffisant', 422);
            }
            return $this->errorResponse('Erreur lors du virement', 500);
        }

        return $this->successResponse([
            'debit' => new TransactionResource($debitTx),
            'credit' => new TransactionResource($creditTx),
        ], 'Virement effectué avec succès');
    }

    public function show(Request $request, $id = null)
    {
        $id = $id ?? $request->route('id') ?? $request->query('id');
        $transaction = $this->transactionRepository->getTransactionById($id);
        return $this->successResponse(new TransactionResource($transaction), 'Détails de la transaction');
    }
}
