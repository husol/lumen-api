<?php

use Illuminate\Database\Seeder;
use App\DataServices\NotiMessage\NotiMessageRepo;

class NotiMessagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $repoNotiMsg = new NotiMessageRepo();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_reviewed_job']);
        $notiMsg->model_msg = 'Công việc %s đã được duyệt.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_matching_job']);
        $notiMsg->model_msg = 'Công việc %s phù hợp với bạn, hãy ứng tuyển ngay.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_applied_job']);
        $notiMsg->model_msg = '%s đã đăng ký công việc %s, hãy xem danh sách ứng viên và chọn ứng viên phù hợp.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_remind_recruiter_checkin']);
        $notiMsg->model_msg = 'Bạn hãy yêu cầu ứng viên check-in công việc %s bằng cách quét mã QR Code trên máy của bạn.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_remind_recruiter_rating']);
        $notiMsg->model_msg = 'Công việc %s đã kết thúc, hãy vào đánh giá ứng viên.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_selected_candidate']);
        $notiMsg->model_msg = 'Chúc mừng %s, bạn đã được chọn cho công việc %s, bạn cần gọi liên hệ trực tiếp cho nhà tuyển dụng để biết chi tiết hơn về công việc. Hãy đến sớm 30 phút trước giờ bắt đầu làm việc.';

        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_remind_candidate_checkin']);
        $notiMsg->model_msg = 'Bạn có công việc %s ngày hôm nay, hãy đến sớm 30 phút trước khi bắt đầu công việc và quét QR Code trên máy nhà tuyển dụng.';
        $notiMsg->save();

        $notiMsg = $repoNotiMsg->firstOrNew(['id_msg' => 'noti_msg_remind_candidate_rating']);
        $notiMsg->model_msg = 'Công việc %s đã kết thúc, hãy vào đánh giá nhà tuyển dụng.';
        $notiMsg->save();
    }
}
