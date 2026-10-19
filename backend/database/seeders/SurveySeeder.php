<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ResponseAnswer;
use App\Models\Survey;
use App\Models\SurveyCategory;
use App\Models\SurveyResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil atau buat admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@survey.com'],
            [
                'name'            => 'Administrator',
                'password'        => 'password1234',
                'role'            => 'admin',
                'approval_status' => 'approved',
                'is_active'       => true,
                'birth_date'      => '1990-01-15',
                'phone'           => '081234567890',
            ]
        );

        // 2. Buat beberapa sample user biasa
        $user1 = User::firstOrCreate(
            ['email' => 'budi@survey.com'],
            [
                'name'            => 'Budi Santoso',
                'password'        => 'password1234',
                'role'            => 'user',
                'approval_status' => 'approved',
                'is_active'       => true,
                'birth_date'      => '1995-05-20',
                'phone'           => '081298765432',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'siti@survey.com'],
            [
                'name'            => 'Siti Rahmawati',
                'password'        => 'password1234',
                'role'            => 'user',
                'approval_status' => 'approved',
                'is_active'       => true,
                'birth_date'      => '1998-11-08',
                'phone'           => '085712345678',
            ]
        );

        $user3 = User::firstOrCreate(
            ['email' => 'andi@survey.com'],
            [
                'name'            => 'Andi Wijaya (Non-Aktif)',
                'password'        => 'password1234',
                'role'            => 'user',
                'approval_status' => 'approved',
                'is_active'       => false,
                'birth_date'      => '1992-03-12',
                'phone'           => '087811223344',
            ]
        );

        $userPending = User::firstOrCreate(
            ['email' => 'rina@survey.com'],
            [
                'name'            => 'Rina Melati (Menunggu Acc)',
                'password'        => 'password1234',
                'role'            => 'user',
                'approval_status' => 'pending',
                'is_active'       => false,
                'birth_date'      => '2000-07-22',
                'phone'           => '081399887766',
            ]
        );

        // Bersihkan survey sample sebelumnya bila ada untuk idempoten
        Survey::where('title', 'like', 'Survey Evaluasi%')->delete();
        Survey::where('title', 'like', 'Survey Preferensi%')->delete();

        // 3. Survey 1: Aktif & Published (Kepuasan Layanan Digital)
        $survey1 = Survey::create([
            'created_by'       => $admin->id,
            'title'            => 'Survey Evaluasi & Kepuasan Layanan Digital 2026',
            'description'      => 'Survey komprehensif untuk mengukur performa sistem, kualitas antarmuka, dan keamanan data pengguna.',
            'status'           => 'published',
            'publish_start_at' => Carbon::now()->subDays(2),
            'publish_end_at'   => Carbon::now()->addDays(30),
        ]);

        // Kategori 1: Pengalaman Pengguna (UI/UX)
        $cat1 = SurveyCategory::create([
            'survey_id'   => $survey1->id,
            'name'        => 'Pengalaman Pengguna (UI/UX)',
            'description' => 'Evaluasi tampilan, kemudahan navigasi, dan kenyamanan visual.',
            'sort_order'  => 1,
        ]);

        // Pertanyaan 1.1
        $q1 = Question::create([
            'survey_category_id' => $cat1->id,
            'question_text'      => 'Seberapa mudah antarmuka aplikasi dipahami oleh pengguna baru?',
            'sort_order'         => 1,
        ]);
        $opt1_A = QuestionOption::create(['question_id' => $q1->id, 'label' => 'A', 'option_text' => 'Sangat Sulit']);
        $opt1_B = QuestionOption::create(['question_id' => $q1->id, 'label' => 'B', 'option_text' => 'Cukup Sulit']);
        $opt1_C = QuestionOption::create(['question_id' => $q1->id, 'label' => 'C', 'option_text' => 'Mudah']);
        $opt1_D = QuestionOption::create(['question_id' => $q1->id, 'label' => 'D', 'option_text' => 'Sangat Mudah & Intuitif']);

        // Pertanyaan 1.2
        $q2 = Question::create([
            'survey_category_id' => $cat1->id,
            'question_text'      => 'Bagaimana penilaian Anda terhadap kecepatan respon aplikasi?',
            'sort_order'         => 2,
        ]);
        $opt2_A = QuestionOption::create(['question_id' => $q2->id, 'label' => 'A', 'option_text' => 'Sangat Lambat (Sering Lag)']);
        $opt2_B = QuestionOption::create(['question_id' => $q2->id, 'label' => 'B', 'option_text' => 'Kurang Cepat']);
        $opt2_C = QuestionOption::create(['question_id' => $q2->id, 'label' => 'C', 'option_text' => 'Cukup Cepat']);
        $opt2_D = QuestionOption::create(['question_id' => $q2->id, 'label' => 'D', 'option_text' => 'Sangat Cepat & Responsif']);

        // Kategori 2: Keamanan & Privasi
        $cat2 = SurveyCategory::create([
            'survey_id'   => $survey1->id,
            'name'        => 'Keamanan & Keandalan Sistem',
            'description' => 'Evaluasi keandalan penyimpanan data dan perlindungan privasi.',
            'sort_order'  => 2,
        ]);

        // Pertanyaan 2.1
        $q3 = Question::create([
            'survey_category_id' => $cat2->id,
            'question_text'      => 'Seberapa yakin Anda terhadap privasi data pribadi Anda pada platform ini?',
            'sort_order'         => 1,
        ]);
        $opt3_A = QuestionOption::create(['question_id' => $q3->id, 'label' => 'A', 'option_text' => 'Sangat Ragu']);
        $opt3_B = QuestionOption::create(['question_id' => $q3->id, 'label' => 'B', 'option_text' => 'Kurang Percaya']);
        $opt3_C = QuestionOption::create(['question_id' => $q3->id, 'label' => 'C', 'option_text' => 'Cukup Percaya']);
        $opt3_D = QuestionOption::create(['question_id' => $q3->id, 'label' => 'D', 'option_text' => 'Sangat Percaya Sepenuhnya']);

        // 4. Sample Respon dari User Budi
        $resp1 = SurveyResponse::create([
            'survey_id' => $survey1->id,
            'user_id'   => $user1->id,
        ]);
        ResponseAnswer::create(['survey_response_id' => $resp1->id, 'question_id' => $q1->id, 'question_option_id' => $opt1_D->id]);
        ResponseAnswer::create(['survey_response_id' => $resp1->id, 'question_id' => $q2->id, 'question_option_id' => $opt2_D->id]);
        ResponseAnswer::create(['survey_response_id' => $resp1->id, 'question_id' => $q3->id, 'question_option_id' => $opt3_C->id]);

        // 5. Sample Respon dari User Siti
        $resp2 = SurveyResponse::create([
            'survey_id' => $survey1->id,
            'user_id'   => $user2->id,
        ]);
        ResponseAnswer::create(['survey_response_id' => $resp2->id, 'question_id' => $q1->id, 'question_option_id' => $opt1_C->id]);
        ResponseAnswer::create(['survey_response_id' => $resp2->id, 'question_id' => $q2->id, 'question_option_id' => $opt2_D->id]);
        ResponseAnswer::create(['survey_response_id' => $resp2->id, 'question_id' => $q3->id, 'question_option_id' => $opt3_D->id]);

        // 6. Survey 2: Draft (Untuk testing fitur edit/publish oleh admin)
        $survey2 = Survey::create([
            'created_by'       => $admin->id,
            'title'            => 'Survey Preferensi Fitur Tambahan Q4 (Draft)',
            'description'      => 'Pengumpulan ide dan fitur prioritas untuk pengembangan aplikasi berikutnya.',
            'status'           => 'draft',
            'publish_start_at' => Carbon::now()->addDays(5),
            'publish_end_at'   => Carbon::now()->addDays(20),
        ]);

        $catDraft = SurveyCategory::create([
            'survey_id'   => $survey2->id,
            'name'        => 'Fitur Baru',
            'description' => 'Pilihan inovasi',
            'sort_order'  => 1,
        ]);

        $qDraft = Question::create([
            'survey_category_id' => $catDraft->id,
            'question_text'      => 'Fitur mana yang paling Anda nantikan?',
            'sort_order'         => 1,
        ]);
        QuestionOption::create(['question_id' => $qDraft->id, 'label' => 'A', 'option_text' => 'Notifikasi Push Realtime']);
        QuestionOption::create(['question_id' => $qDraft->id, 'label' => 'B', 'option_text' => 'Export Laporan PDF/Excel']);
        QuestionOption::create(['question_id' => $qDraft->id, 'label' => 'C', 'option_text' => 'Integrasi SSO Google/OAuth']);
        QuestionOption::create(['question_id' => $qDraft->id, 'label' => 'D', 'option_text' => 'Tema Kustomisasi Warna']);
    }
}
