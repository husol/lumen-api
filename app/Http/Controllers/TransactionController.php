<?php

namespace App\Http\Controllers;

use App\DataServices\Transaction\TransactionRepoInterface;
use App\Firebase;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $repoTrans;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionRepoInterface $repoTransaction)
    {
        $this->repoTrans = $repoTransaction;
    }

    public function update(Request $request)
    {
        /*Example data:
        [vpc_AdditionalData] => 400555
        [vpc_Amount] => 1000000
        [vpc_BatchNo] => 20180322
        [vpc_CardType] => Visa
        [vpc_Command] => pay
        [vpc_CurrencyCode] => VND
        [vpc_Locale] => vn
        [vpc_MerchTxnRef] => 6
        [vpc_Merchant] => SMLTEST
        [vpc_OrderInfo] => 1
        [vpc_ResponseCode] => 0
        [vpc_TransactionNo] => 835514058
        [vpc_Version] => 2.0
        [vpc_SecureHash] => B8F2A9E60C194AD7169E3B52D5887024*/

        $callbackReq = $request->all();

        if (!(isset($callbackReq['vpc_MerchTxnRef']) && isset($callbackReq['vpc_OrderInfo']))) {
            return false;
        }

        //Initial firebase database
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $idTransaction = $callbackReq['vpc_MerchTxnRef'];
        $idOrder = $callbackReq['vpc_OrderInfo'];

        $myTransaction = $this->repoTrans->findWhere([
            'id' => $idTransaction,
            'id_order' => $idOrder
        ])->first();

        if (empty($myTransaction)) {
            return false;
        }

        //Update transaction
        $dataUpdated = [];
        $dataUpdated['amount'] = floatval($callbackReq['vpc_Amount'])/100;
        if (isset($callbackReq['vpc_CardType'])) {
            $dataUpdated['card_type'] = $callbackReq['vpc_CardType'];
        }
        $dataUpdated['command'] = $callbackReq['vpc_Command'];

        $dataUpdated['transaction_no'] = 0;
        if (isset($callbackReq['vpc_TransactionNo'])) {
            $dataUpdated['transaction_no'] = $callbackReq['vpc_TransactionNo'];
        }
        $dataUpdated['currency_code'] = $callbackReq['vpc_CurrencyCode'];
        $dataUpdated['additional_data'] = json_encode($callbackReq);

        $dataUpdated['response_msg'] = "Giao dịch thành công";
        $dataUpdated['status'] = Transaction::STATUS_SUCCESS;

        $responseCode = $callbackReq['vpc_ResponseCode'];

        if ($responseCode > 0) {
            switch (intval($responseCode)) {
                case 1:
                    $responseMsg = "Ngân hàng từ chối thanh toán: Thẻ/tài khoản bị khóa";
                    break;
                case 3:
                    $responseMsg = "Thẻ hết hạn";
                    break;
                case 4:
                    $responseMsg = "Quá số lần giao dịch cho phép. (Sai OTP, quá hạn mức trong ngày)";
                    break;
                case 5:
                    $responseMsg = "Không có phản hồi từ ngân hàng";
                    break;
                case 6:
                    $responseMsg = "Lỗi giao tiếp với ngân hàng";
                    break;
                case 7:
                    $responseMsg = "Tài khoản không đủ tiền";
                    break;
                case 8:
                    $responseMsg = "Lỗi dữ liệu truyền";
                    break;
                case 9:
                    $responseMsg = "Kiểu giao dịch không được hỗ trợ";
                    break;
                case 11:
                    $responseMsg = "Kiểm tra thẻ thành công";
                    break;
                case 12:
                case 24:
                    $responseMsg = "Thanh toán không thành công. Giao dịch vượt quá số tiền cho phép";
                    break;
                case 13:
                    $responseMsg = "Bạn chưa đăng ký dịch vụ thanh toán trực tuyến.".
                        " Vui lòng liên hệ ngân hàng của bạn";
                    break;
                case 14:
                    $responseMsg = "Sai mã OTP";
                    break;
                case 15:
                    $responseMsg = "Sai mật khẩu tĩnh";
                    break;
                case 16:
                    $responseMsg = "Tên chủ thẻ không đúng";
                    break;
                case 17:
                    $responseMsg = "Số thẻ không đúng";
                    break;
                case 18:
                    $responseMsg = "Ngày ban hành thẻ không đúng";
                    break;
                case 19:
                    $responseMsg = "Ngày hết hạn thẻ không đúng";
                    break;
                case 20:
                case 22:
                    $responseMsg = "Giao dịch không thành công";
                    break;
                case 21:
                    $responseMsg = "Mã OTP hết hạn";
                    break;
                case 23:
                    $responseMsg = "Thanh toán không được chấp nhận.".
                        " Thẻ/tài khoản của bạn không đủ chuẩn cho việc thanh toán";
                    break;
                case 25:
                    $responseMsg = "Giao dịch vượt quá số tiền cho phép";
                    break;
                case 26:
                    $responseMsg = "Các giao dịch đang chờ sự xác nhận từ ngân hàng";
                    break;
                case 27:
                    $responseMsg = "Bạn vừa nhập sai thông tin xác thực";
                    break;
                case 28:
                    $responseMsg = "Thanh toán không thành công.".
                        " Giao dịch vượt quá thời gian cho phép";
                    break;
                case 29:
                    $responseMsg = "Giao dịch lỗi.".
                        " Liên hệ ngân hàng của bạn để biết thêm thông tin";
                    break;
                case 30:
                    $responseMsg = "Thanh toán không thành công. Số tiền ít hơn giới hạn tối thiểu";
                    break;
                case 31:
                    $responseMsg = "Không tìm thấy đơn hàng";
                    break;
                case 32:
                    $responseMsg = "Đơn hàng không thể thanh toán";
                    break;
                case 33:
                    $responseMsg = "Đơn hàng bị trùng lắp";
                    break;
                default:
                    $responseMsg = "Lỗi không xác định";
            }
            $dataUpdated['response_msg'] = $responseMsg;
            $dataUpdated['status'] = Transaction::STATUS_FAIL;
        }

        $myTransaction = $this->repoTrans->update($myTransaction->id, $dataUpdated);

        //Update transaction status on firebase database
        $database->getReference("transactions/{$myTransaction->transaction_code}")->set($myTransaction);

        return true;
    }

    public function cancel(Request $request, $id)
    {
        $myTrans = $this->repoTrans->find($id);

        if (empty($myTrans)) {
            return false;
        }

        //Update transaction status in DB
        $dataUpdated = [];
        $dataUpdated['status'] = 0;
        $dataUpdated['response_msg'] = "Người dùng hủy giao dịch";
        $myTrans = $this->repoTrans->update($myTrans->id, $dataUpdated);

        //Update Transaction to Firebase
        $firebase = (new Firebase)->create();
        $database = $firebase->getDatabase();

        $database->getReference("transactions/{$myTrans->transaction_code}")->set($myTrans);
        return true;
    }
}
