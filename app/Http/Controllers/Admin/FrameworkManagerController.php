<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentFramework;
use App\Models\AssessmentPillar;
use App\Models\AssessmentQuestionBank;
use Illuminate\Http\Request;

class FrameworkManagerController extends Controller
{
    public function index()
    {
        $frameworks = AssessmentFramework::with('pillars.questions')->get();
        return view('admin.frameworks.index', compact('frameworks'));
    }

    public function updatePillar(Request $request, AssessmentPillar $pillar)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:0',
            'is_critical' => 'required|boolean',
            'name' => 'required|string|max:255',
        ]);

        $pillar->update($validated);

        return back()->with('success', 'Pillar updated successfully.');
    }

    public function storeQuestion(Request $request)
    {
        $validated = $request->validate([
            'framework_id' => 'required|exists:assessment_frameworks,id',
            'pillar_id' => 'required|exists:assessment_pillars,id',
            'level' => 'required|in:snapshot,full',
            'question_code' => 'required|string|max:20',
            'question_text' => 'required|string',
        ]);

        AssessmentQuestionBank::create($validated);

        return back()->with('success', 'Question added successfully.');
    }

    public function updateQuestion(Request $request, AssessmentQuestionBank $question)
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'is_active' => 'required|boolean',
        ]);

        $question->update($validated);

        return back()->with('success', 'Question updated successfully.');
    }

    public function destroyQuestion(AssessmentQuestionBank $question)
    {
        $question->delete();
        return back()->with('success', 'Question deleted successfully.');
    }
}
