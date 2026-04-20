<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_questions_are_not_repeated_and_quiz_finishes_when_all_solved(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create(['name' => '試験A']);

        $question1 = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $user->id,
            'question_text' => '問題1',
        ]);
        $question2 = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $user->id,
            'question_text' => '問題2',
        ]);

        $correct1 = Option::create([
            'question_id' => $question1->id,
            'option_text' => 'Q1-正解',
            'is_correct' => true,
        ]);
        $correct2 = Option::create([
            'question_id' => $question2->id,
            'option_text' => 'Q2-正解',
            'is_correct' => true,
        ]);

        $this->actingAs($user)
            ->get(route('quiz.index', ['exam' => $exam->id, 'mode' => 'normal']))
            ->assertOk();

        $firstQuestionId = (int) session('quiz_state.current_question_id');
        $firstCorrectOptionId = $firstQuestionId === $question1->id ? $correct1->id : $correct2->id;

        $this->actingAs($user)
            ->post(route('quiz.answer', $exam), [
                'question_id' => $firstQuestionId,
                'selected_options' => [$firstCorrectOptionId],
            ])
            ->assertOk()
            ->assertViewHas('is_correct', true);

        $this->actingAs($user)
            ->post(route('quiz.next', $exam))
            ->assertOk();

        $secondQuestionId = (int) session('quiz_state.current_question_id');
        $this->assertNotEquals($firstQuestionId, $secondQuestionId);

        $secondCorrectOptionId = $secondQuestionId === $question1->id ? $correct1->id : $correct2->id;

        $this->actingAs($user)
            ->post(route('quiz.answer', $exam), [
                'question_id' => $secondQuestionId,
                'selected_options' => [$secondCorrectOptionId],
            ])
            ->assertOk()
            ->assertViewHas('is_finished', true);
    }

    public function test_reload_starts_quiz_from_initial_state(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create(['name' => '試験B']);

        $question = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $user->id,
            'question_text' => '問題B',
        ]);

        $correct = Option::create([
            'question_id' => $question->id,
            'option_text' => '正解',
            'is_correct' => true,
        ]);

        $this->actingAs($user)
            ->get(route('quiz.index', ['exam' => $exam->id, 'mode' => 'normal']))
            ->assertOk();

        $currentQuestionId = (int) session('quiz_state.current_question_id');

        $this->actingAs($user)
            ->post(route('quiz.answer', $exam), [
                'question_id' => $currentQuestionId,
                'selected_options' => [$correct->id],
            ])
            ->assertOk()
            ->assertViewHas('is_correct', true);

        $this->assertCount(1, session('quiz_state.solved_question_ids', []));

        $this->actingAs($user)
            ->withHeaders(['Cache-Control' => 'max-age=0'])
            ->get(route('quiz.index', ['exam' => $exam->id, 'mode' => 'normal']))
            ->assertOk();

        $this->assertCount(0, session('quiz_state.solved_question_ids', []));
    }

    public function test_flagged_mode_uses_only_flagged_questions(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create(['name' => '試験C']);

        $flaggedQuestion = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $user->id,
            'question_text' => 'フラグ付き問題',
        ]);
        $otherQuestion = Question::create([
            'exam_id' => $exam->id,
            'created_by' => $user->id,
            'question_text' => '通常問題',
        ]);

        Option::create([
            'question_id' => $flaggedQuestion->id,
            'option_text' => '選択肢1',
            'is_correct' => true,
        ]);
        Option::create([
            'question_id' => $otherQuestion->id,
            'option_text' => '選択肢2',
            'is_correct' => true,
        ]);

        $user->flaggedQuestions()->attach($flaggedQuestion->id);

        $this->actingAs($user)
            ->get(route('quiz.index', ['exam' => $exam->id, 'mode' => 'flagged']))
            ->assertOk();

        $this->assertSame($flaggedQuestion->id, (int) session('quiz_state.current_question_id'));
    }

    public function test_random_count_mode_blocks_navigation_to_other_pages(): void
    {
        $user = User::factory()->create();
        $exam = Exam::create(['name' => '試験D']);

        foreach (range(1, 3) as $i) {
            $question = Question::create([
                'exam_id' => $exam->id,
                'created_by' => $user->id,
                'question_text' => '問題D-' . $i,
            ]);

            Option::create([
                'question_id' => $question->id,
                'option_text' => '選択肢D-' . $i,
                'is_correct' => true,
            ]);
        }

        $this->actingAs($user)
            ->get(route('quiz.index', ['exam' => $exam->id, 'mode' => 'random_count', 'count' => 2]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('quiz.resume', ['exam' => $exam->id]));
    }
}
