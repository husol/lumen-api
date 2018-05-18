<?php

namespace App\Http\Controllers;

use App\DataServices\Order\OrderRepoInterface;
use App\DataServices\Job\JobRepo;
use App\DataServices\Transaction\TransactionRepo;
use App\Firebase;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Error;
use App\Common;

class OrderController extends Controller
{
    protected $repoOrder;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(OrderRepoInterface $repoOrder)
    {
        $this->repoOrder = $repoOrder;
    }

    public function update(Request $request)
    {
        //Validate
        $rules = [];
        $rules['id_job'] = 'required|numeric';
        $rules['gateway'] = 'required|numeric';
        $rules['ticket_no'] = 'required|ip';
        if ($request->has('id')) {
            $rules['id'] = 'required|numeric';
        }

        $this->validate($request, $rules);

        $err = new Error();
        //Validate id_order and id_job
        if ($request->has('id_order')) {
            $id = $request->input('id_order');
            $myOrder = $this->repoOrder->find($id);
            if (empty($myOrder)) {
                $err->setError('not_found', "No record with id = $id");
                return responseJson($err->getErrors(), 404);
            }
        }

        $idJob = $request->input('id_job');
        $gateway = $request->input('gateway');
        $ticketNo = $request->input('ticket_no');
        $loggedUser = Common::getLoggedUserInfo();

        if (empty($loggedUser->id_company)) {
            $err->setError('not_found', "Not found company with id_user = $loggedUser->id");
            return responseJson($err->getErrors(), 404);
        }

        $repoJob = new JobRepo();
        $myJob = $repoJob->findWhere([
            'id' => $idJob,
            'id_company' => $loggedUser->id_company
        ])->first();

        if (empty($myJob)) {
            $err->setError('not_found', "Not found job with id = $idJob and id_company = $loggedUser->id_company");
            return responseJson($err->getErrors(), 404);
        }

        //Create / update order
        $dataUpdated = [];
        $dataUpdated['id_user'] = $loggedUser->id;
        $dataUpdated['id_job'] = $myJob->id;
        $dataUpdated['type'] = Order::TYPE_ONE_TIME;
        $dataUpdated['amount'] = $myJob->total*1000;//converted to VND

        if (isset($myOrder)) {
            $myOrder = $this->repoOrder->update($myOrder->id, $dataUpdated);
        } else {
            $myOrder = $this->repoOrder->create($dataUpdated);
        }

        //Create transaction
        $dataCreated = [];
        $dataCreated['id_order'] = $myOrder->id;
        $dataCreated['gateway'] = $gateway;
        $dataCreated['amount'] = $myOrder->amount;
        $dataCreated['status'] = Transaction::STATUS_PENDING;
        $repoTrans = new TransactionRepo();
        $myTransaction = $repoTrans->create($dataCreated);

        //Update Transaction to Firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $ref = $database->getReference("transactions")->push($myTransaction);
        $transactionCode = $ref->getKey();
        $myTransaction = $repoTrans->update($myTransaction->id, ['transaction_code' => $transactionCode]);

        $response = [
            'transaction_code' => $myTransaction->transaction_code,
            'id_transaction' => $myTransaction->id,
            'id_order' => $myTransaction->id_order
        ];
        if ($myTransaction->gateway == 1) {
            //Build napas_url
            $secretKey = env('NAPAS_SECRET_KEY');

            /*string hashAllField = secretKey +
                provider.vpc_AccessCode +
                provider.vpc_Amount +
                provider.vpc_BackURL +
                provider.vpc_CardType (optional) +
                provider.vpc_Command +
                provider.vpc_CurrencyCode +
                provider.vpc_Locale +
                vpc_MerchTxnRef +
                vpc_Merchant ("SMLTEST")  +
                vpc_OrderInfo +
                vpc_PaymentGateway (optional)+
                provider.vpc_ReturnURL +
                vpc_TicketNo +
                vpc_Version ("2.0");
            */

            $params = [
                'vpc_AccessCode' => env('NAPAS_ACCESS_CODE'),
                'vpc_Amount' => strval($myTransaction->amount*100),//make sure integer value for banking rule
                'vpc_BackURL' => env('API_ROOTURL') . "/payments/cancel/{$myTransaction->id}",
                'vpc_Command' => 'pay',
                'vpc_CurrencyCode' => 'VND',
                'vpc_Locale' => 'vn',
                'vpc_MerchTxnRef' => $myTransaction->id,
                'vpc_Merchant' => env('NAPAS_MERCHANT_ID'),
                'vpc_OrderInfo' => $myTransaction->id_order,
                'vpc_ReturnURL' => env('API_ROOTURL') . "/payments/callback",
                'vpc_TicketNo' => $ticketNo,
                'vpc_Version' => env('NAPAS_VERSION')
            ];

            $vpcURL = env('NAPAS_GATEWAY_URL').'?';
            $strFieldsConcat = $secretKey;
            $appendAmp = 0;
            foreach ($params as $key => $value) {
                //Create the md5 input and URL leaving out any fields that have no value
                if (strlen($value) > 0) {
                    //This ensures the first paramter of the URL is preceded by the '?' char
                    if ($appendAmp == 0) {
                        $vpcURL .= urlencode($key) . '=' . urlencode($value);
                        $appendAmp = 1;
                    } else {
                        $vpcURL .= '&' . urlencode($key) . "=" . urlencode($value);
                    }
                    $strFieldsConcat .= $value;
                }
            }
            if (strlen($secretKey) > 0) {
                $vpcURL .= "&vpc_SecureHash=" . strtoupper(md5($strFieldsConcat));
            }

            $response['napas_url'] = $vpcURL;
        }

        return responseJson($response);
    }
}
