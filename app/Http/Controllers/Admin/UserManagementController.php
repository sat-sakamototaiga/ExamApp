<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Exception;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $allowedRoles = [User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT];
        $selectedRole = $request->query('role', User::ROLE_ADMIN);

        if (! in_array($selectedRole, $allowedRoles, true)) {
            $selectedRole = User::ROLE_ADMIN;
        }

        $users = User::query()
            ->withCount(['students', 'teachers'])
            ->where('role', $selectedRole)
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', compact('users', 'selectedRole'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', Rule::in([User::ROLE_TEACHER, User::ROLE_STUDENT])],
            'subject_name' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_TEACHER),
                'nullable',
                'string',
                'max:255',
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'subject_name' => $validated['role'] === User::ROLE_TEACHER ? trim((string) ($validated['subject_name'] ?? '')) : null,
            'password' => $validated['password'],
        ]);

        return back()->with('success', 'ユーザーを登録しました。');
    }

    public function downloadImportTemplate(CsvImportService $csvImportService)
    {
        $header = ['名前', 'メールアドレス', 'ロール', '教科名', 'パスワード'];
        $sampleRows = [
            ['田中 太郎', 'teacher@example.com', User::ROLE_TEACHER, '数学', 'password123'],
            ['佐藤 花子', 'student@example.com', User::ROLE_STUDENT, '', 'password123'],
        ];

        return $csvImportService->streamTemplateDownload('users_import_template.csv', $header, $sampleRows);
    }

    public function import(Request $request, CsvImportService $csvImportService): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $reader = $csvImportService->createReaderFromUpload($request->file('csv_file'));
        } catch (Exception $e) {
            return back()->withInput()->withErrors([$e->getMessage()]);
        }

        $csvStream = $reader['stream'];
        $header = $reader['header'];

        $expectedHeader = ['名前', 'メールアドレス', 'ロール', '教科名', 'パスワード'];
        if ($header !== $expectedHeader) {
            fclose($csvStream);

            return back()->withInput()->withErrors([
                'CSVファイルのヘッダー形式が正しくありません。期待されるヘッダー: ' . implode(', ', $expectedHeader),
            ]);
        }

        $importedCount = 0;
        $errorMessages = [];
        $lineNumber = 1;

        while (($row = fgetcsv($csvStream)) !== false) {
            $lineNumber++;

            if ($row === [null]) {
                continue;
            }

            if (count($row) !== count($header)) {
                $errorMessages[] = "{$lineNumber}行目: 列数がヘッダーと一致しません。スキップしました。";
                continue;
            }

            $data = array_combine($header, $row);
            if ($data === false) {
                $errorMessages[] = "{$lineNumber}行目: CSVデータの読み取りに失敗しました。";
                continue;
            }

            $data = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $data);
            $role = $data['ロール'] ?? '';

            if (! in_array($role, [User::ROLE_TEACHER, User::ROLE_STUDENT], true)) {
                $errorMessages[] = "{$lineNumber}行目: ロールは teacher または student を指定してください。";
                continue;
            }

            if (empty($data['名前']) || empty($data['メールアドレス']) || empty($data['パスワード'])) {
                $errorMessages[] = "{$lineNumber}行目: 名前、メールアドレス、パスワードは必須です。";
                continue;
            }

            if ($role === User::ROLE_TEACHER && empty($data['教科名'])) {
                $errorMessages[] = "{$lineNumber}行目: 教師ロールでは教科名は必須です。";
                continue;
            }

            if (! filter_var($data['メールアドレス'], FILTER_VALIDATE_EMAIL)) {
                $errorMessages[] = "{$lineNumber}行目: メールアドレスの形式が不正です。";
                continue;
            }

            if (User::where('email', Str::lower($data['メールアドレス']))->exists()) {
                $errorMessages[] = "{$lineNumber}行目: メールアドレスが既に登録されています。";
                continue;
            }

            if (mb_strlen($data['パスワード']) < 8) {
                $errorMessages[] = "{$lineNumber}行目: パスワードは8文字以上で入力してください。";
                continue;
            }

            try {
                User::create([
                    'name' => $data['名前'],
                    'email' => Str::lower($data['メールアドレス']),
                    'role' => $role,
                    'subject_name' => $role === User::ROLE_TEACHER ? $data['教科名'] : null,
                    'password' => $data['パスワード'],
                ]);
                $importedCount++;
            } catch (Exception $e) {
                $errorMessages[] = "{$lineNumber}行目: インポート中にエラーが発生しました - {$e->getMessage()}";
                Log::error("User CSV Import Error on line {$lineNumber}: " . $e->getMessage(), ['data' => $data]);
            }
        }

        fclose($csvStream);

        if (count($errorMessages) > 0) {
            if ($importedCount > 0) {
                array_unshift($errorMessages, "{$importedCount}件のユーザーをインポートしましたが、一部の行でエラーが発生しました。");
            }

            return back()->withInput()->withErrors($errorMessages);
        }

        return back()->with('success', "{$importedCount}件のユーザーを正常にインポートしました。");
    }

    public function accuracy(): View
    {
        $resultSummary = DB::table('exam_results')
            ->select(
                'user_id',
                DB::raw('SUM(score) as total_score'),
                DB::raw('SUM(question_count) as total_questions')
            )
            ->groupBy('user_id');

        $rows = User::query()
            ->leftJoinSub($resultSummary, 'result_summary', function ($join) {
                $join->on('users.id', '=', 'result_summary.user_id');
            })
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                DB::raw('COALESCE(result_summary.total_score, 0) as total_score'),
                DB::raw('COALESCE(result_summary.total_questions, 0) as total_questions')
            )
            ->orderBy('users.id')
            ->paginate(20);

        $rows->getCollection()->transform(function ($row) {
            $row->accuracy_rate = ((int) $row->total_questions > 0)
                ? round(((int) $row->total_score / (int) $row->total_questions) * 100, 1)
                : null;

            return $row;
        });

        return view('admin.accuracy', compact('rows'));
    }

    public function assignments(): View
    {
        $teachers = User::query()
            ->where('role', User::ROLE_TEACHER)
            ->with('students:id,name,email,role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        $students = User::query()
            ->where('role', User::ROLE_STUDENT)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);

        return view('admin.assignments', compact('teachers', 'students'));
    }

    public function storeAssignment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        $students = User::query()
            ->whereIn('id', $validated['student_ids'])
            ->get();

        if (! $teacher->isTeacher()) {
            return back()->withErrors(['teacher_id' => '指定したユーザーは教師ではありません。']);
        }

        if ($students->count() !== count($validated['student_ids'])) {
            return back()->withErrors(['student_ids' => '指定した生徒に不正なユーザーが含まれています。']);
        }

        $invalidStudent = $students->first(fn (User $student) => ! $student->isStudent());
        if ($invalidStudent !== null) {
            return back()->withErrors(['student_ids' => '指定したユーザーの中に生徒ではないユーザーが含まれています。']);
        }

        $teacher->students()->syncWithoutDetaching($students->pluck('id')->all());

        return back()->with('success', count($validated['student_ids']) . '件の教師と生徒の紐付けを追加しました。');
    }

    public function destroyAssignment(User $teacher, User $student): RedirectResponse
    {
        if (! $teacher->isTeacher() || ! $student->isStudent()) {
            abort(422, '教師または生徒の指定が不正です。');
        }

        $teacher->students()->detach($student->id);

        return back()->with('success', '教師と生徒の紐付けを解除しました。');
    }
}
