<?php namespace Eprog\Manager\Controllers;


use Backend\Models\User as BackendUser;
use RainLab\User\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Eprog\Manager\Models\Invoice;
use Eprog\Manager\Models\Order;
use October\Rain\Exception\ValidationException;
use Hash;

class Api extends Controller
{

    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $existingToken = $user->tokens()->where('name', 'api-token')->first();

        if ($existingToken) {
            $token = $existingToken->plainTextToken ?? null;
            if (!$token) {
                $existingToken->delete();
                $token = $user->createToken('api-token')->plainTextToken;
            }
        } else {
            $token = $user->createToken('api-token')->plainTextToken;
        }

        return response()->json([
            'token' => $token
        ]);
    }

    public function bearer(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'token' => $user->bearer
        ]);
    }


    public function invoices(Request $request)
    {
        $user = $request->attributes->get('backendUser');
 
        $email= $request->get('email'); 

        if(empty($email)) return "";

        $nip = User::where("email",$email)->first()['firm_nip'] ?? "";
        
        $term = urldecode($request->get('term')); 
        $term_amount = str_replace([' ', ','], ['', '.'], $term);

        $query = Invoice::where("id",">",0);
        
        if($nip != "")
            $query = $query->where("buyer_nip",$nip);
        else
            $query = $query->where("buyer_email",$email);

        $query =  $query->select("id", "type","nr","ksefNumber","netto","brutto","vat","currency","seller_name","make_at","create_at", "xml");

        if (!empty($term)) {
            $query->where(function($q) use ($term,$term_amount) {
                $q->where('nr', 'like', "%{$term}%")
                  ->orWhere('ksefNumber', 'like', "%{$term}%")
                  ->orWhere('netto', 'like', "%{$term_amount}%")
                  ->orWhere('brutto', 'like', "%{$term_amount}%")
                  ->orWhere('vat', 'like', "%{$term_amount}%")
                  ->orWhere('currency', 'like', "%{$term}%")
                  ->orWhere('make_at', 'like', "%{$term}%")
                  ->orWhere('create_at', 'like', "%{$term}%");
            });
        }

  
        $invoices = $query->get()->map(function ($o) {

            if($o->xml){
                $xml = simplexml_load_string($o->xml);

                $namespaces = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('f', $namespaces['']);

                $data = $xml->xpath('//f:Fa/f:Platnosc/f:DataZaplaty');

                $o->payment = $data ? (string)$data[0] : "";
            }
            else
                $o->payment = "";

            return $o;

        });
 

        return response()->json([
            'invoices' => $invoices->toArray()
        ]);
    }


    public function orders(Request $request)
    {
        $user = $request->attributes->get('backendUser');

        $email= $request->get('email'); 

        if(empty($email)) return "";

        $nip = User::where("email",$email)->first()['firm_nip'] ?? "";

        $term = urldecode($request->get('term')); 
        $term_amount = str_replace([' ', ','], ['', '.'], $term);

        $query = Order::where("id",">",0);

        if($nip != "")
            $query = $query->where("buyer_nip",$nip);
        else
            $query = $query->where("buyer_email",$email);
        
        $query =   $query->select("id","nr","netto","brutto","vat","currency","seller_name","create_at","xml");

        if (!empty($term)) {
            $query->where(function($q) use ($term,$term_amount) {
                $q->where('nr', 'like', "%{$term}%")
                  ->orWhere('netto', 'like', "%{$term_amount}%")
                  ->orWhere('brutto', 'like', "%{$term_amount}%")
                  ->orWhere('vat', 'like', "%{$term_amount}%")
                  ->orWhere('currency', 'like', "%{$term}%")
                  ->orWhere('create_at', 'like', "%{$term}%");
            });
        }


        $orders = $query->get()->map(function ($o) {

            if($o->xml){
                $xml = simplexml_load_string($o->xml);

                $namespaces = $xml->getNamespaces(true);
                $xml->registerXPathNamespace('f', $namespaces['']);

                $data = $xml->xpath('//f:Fa/f:Platnosc/f:DataZaplaty');

                $o->payment = $data ? (string)$data[0] : "";
            }
            else
                $o->payment = "";

            return $o;

        });

        return response()->json([
            'orders' => $orders->toArray()
        ]);
    }

}