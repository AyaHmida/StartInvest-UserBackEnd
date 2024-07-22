<?php

namespace App\Http\Controllers;

use App\Models\Flouci;
use App\Models\Startup;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Générer un paiement via l'API Flouci.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generatePayment(Request $request)
    {
        $id_startup = $request->input('id_startup');
        $flouciData = $this->getSecret($id_startup);

        if ($flouciData) {
            $response = Http::post('https://developers.flouci.com/api/generate_payment', [
                'app_token' => $flouciData['app_public'],
                'app_secret' => $flouciData['app_secret'],
                'amount' => number_format($flouciData['amount'], 0, '.', ''),
                'accept_card' => true,
                'session_timeout_secs' => 1200,
                'success_link' => "http://127.0.0.1:3000/success",
                'fail_link' => "http://127.0.0.1:3000/failed",
                'developer_tracking_id' => uniqid(),
            ]);

            if ($response->successful()) {
                $transaction = $this->saveTransaction($request, $response->json()['result']['payment_id'], $flouciData['id_flouci']);
                $flouci = Flouci::find($flouciData['id_flouci']);
                $flouci->montant_total += $flouciData['amount'];
                $flouci->save();

                return $response->json();
            } else {
                return response()->json(['error' => 'Une erreur est survenue lors de la génération du paiement.'], $response->status());
            }
        } else {
            return response()->json(['error' => 'Données Flouci non trouvées pour l\'ID de startup donné.'], 404);
        }
    }


    public function investmentHistory(Request $request)
    {
        $userId = $request->user()->id;
        $investmentHistory = Transaction::where('id_investisseur', $userId)
            ->with('flouci.startup')
            ->get()
            ->map(function ($transaction) {
                return [
                    'startup_name' => optional($transaction->flouci->startup)->nom,
                    'amount' => optional($transaction->flouci)->amount,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                ];
            });
        return response()->json($investmentHistory);
    }
    public function saveTransaction(Request $request, $payment_id, $id_flouci) {
        $user = Auth::user();
        if ($user) {
            $transaction = new Transaction();
            $transaction->payment_id = $payment_id;
            $transaction->id_investisseur = $user->id;
            $transaction->id_floucis = $id_flouci;
            $transaction->save();
            return $transaction;
        } else {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }
    }
    public function verifyPayment($payment_id)
    {
        $url = 'https://developers.flouci.com/api/verify_payment/' . $payment_id;
        $headers = [
            'Content-Type' => 'application/json',
            'apppublic' => 'dcc7d5ae-f0b8-4d68-9d95-4d9f735cecc2',
            'appsecret' => '74556c91-2025-4bbb-8664-ae6a16b536fb'
        ];
        $response = Http::withHeaders($headers)->get($url);
        if ($response->successful()) {
            return $response->json();
        } else {
            return response()->json(['error' => 'Une erreur est survenue lors de la vérification du paiement.'], $response->status());
        }
    }
    public function getSecret($id_startup)
    {
        $flouci = Flouci::where('id_startup', $id_startup)->first();

        if ($flouci) {
            return [
                'app_public' => $flouci->app_public,
                'app_secret' => $flouci->app_secret,
                'amount' => $flouci->amount,
                'id_flouci' => $flouci->id,
            ];
        }

        return null;
    }

    // ce fonction ne pas utilise
    public function checkFlouciExistence(Request $request)
    {
        $startupId = $request->input('id_startup');

        $flouciExists = Flouci::where('id_startup', $startupId)->exists();

        return response()->json(['exists' => $flouciExists]);
    }
    public function investorsAndTransactionDatesOfUserStartup(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $startup = $user->startups->first();

        if (!$startup) {
            return response()->json(['error' => 'User has no associated startup'], 404);
        }

        $startupId = $startup->id;

        $transactions = Transaction::whereHas('flouci', function ($query) use ($startupId) {
            $query->where('id_startup', $startupId);
        })->with('investisseur')->get();

        if ($transactions->isEmpty()) {
            return response()->json(['error' => 'No transactions found for the user and associated startup'], 404);
        }

        $investorsAndDates = $transactions->map(function ($transaction) {
            return [
                'investor_name' => optional($transaction->investisseur)->name, 
                'transaction_date' => $transaction->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json($investorsAndDates);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'app_public' => 'required',
            'app_secret' => 'required',
            'amount'=>'required'
        ]);
        if ($validator->fails()) {
            $data = [
                'status' => 422,
                'message' => $validator->messages()
            ];
            return response()->json($data, 422);
        } else {
            $authenticatedUser = Auth::user();
            if($authenticatedUser->type !== 'fondateur') {
                $data = [
                    'status' => 403,
                    'message' => 'Vous n\'avez pas les autorisations nécessaires pour effectuer cette action.'
                ];
                return response()->json($data, 403);
            }
            $compte = new Flouci();
            $compte->app_public = $request->app_public;
            $compte->app_secret = $request->app_secret;
            $compte->amount = $request->amount;
            $compte->id_startup = $authenticatedUser->startups->first()->id;
            $compte->save();
            $data = [
                'status' => 200,
                'message' => 'Données créées avec succès'
            ];
            return response()->json($data, 200);
        }

    }
    public function getCompteFlouci(){
        $user = auth()->user();
        $startup = $user->startups->first();
            $compte = Flouci::where('id_startup', $startup->id)->first();
            if ($compte) {
                return response()->json( $compte, 200);
            } else {
                return response()->json(['message' => 'Compte Flouci non trouvé pour cette startup'], 404);
            }

    }
}
