@extends('admin.layouts.app')

@section('header', 'Framework Management')

@section('content')
<div class="bg-gray-50 p-6 rounded-2xl shadow-inner min-h-screen" x-data="{ 
    tab: 'PHI', 
    showPillarModal: false, 
    showQuestionModal: false,
    selectedPillar: null,
    selectedQuestion: null,
    modalMode: 'create'
}">
    
    <!-- Framework Tabs -->
    <div class="flex items-center gap-6 mb-8 border-b border-gray-200">
        @foreach($frameworks as $fw)
            <button @click="tab = '{{ $fw->code }}'" 
                    :class="tab === '{{ $fw->code }}' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                    class="pb-4 px-2 font-black uppercase tracking-widest text-sm border-b-4 transition-all">
                {{ $fw->name }} ({{ $fw->code }})
            </button>
        @endforeach
    </div>

    @foreach($frameworks as $fw)
    <div x-show="tab === '{{ $fw->code }}'" class="space-y-12">
        
        <!-- Pillars Section -->
        <div class="bg-white shadow-xl border border-gray-200 rounded-3xl overflow-hidden">
            <div class="px-8 py-6 bg-slate-900 border-b border-white/10 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-black text-white uppercase tracking-widest">Pillars & Weights</h2>
                    <p class="text-xs text-slate-400 font-bold mt-1 tracking-tighter italic">Manage the importance and criticality of each assessment area.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Code</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Pillar Name</th>
                            <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Weight</th>
                            <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Critical</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @foreach($fw->pillars as $pillar)
                        <tr class="hover:bg-gray-50/50 transition-colors group">
                            <td class="px-8 py-5 text-sm font-black text-slate-400">{{ $pillar->code }}</td>
                            <td class="px-8 py-5">
                                <div class="text-sm font-black text-slate-900 uppercase tracking-tight">{{ $pillar->name }}</div>
                            </td>
                            <td class="px-8 py-5 text-center">
                                <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-lg text-xs font-black border border-blue-100 italic">{{ number_format($pillar->weight, 2) }}</span>
                            </td>
                            <td class="px-8 py-5 text-center">
                                @if($pillar->is_critical)
                                    <span class="bg-red-500 text-white px-2 py-0.5 rounded text-[10px] font-black uppercase shadow-lg shadow-red-500/20">Critical</span>
                                @else
                                    <span class="text-slate-300 text-[10px] font-black uppercase tracking-widest">Standard</span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button @click="selectedPillar = {{ $pillar }}; showPillarModal = true" class="text-blue-600 hover:text-blue-800 font-bold text-xs uppercase tracking-widest">Edit</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Questions Section -->
        <div class="bg-white shadow-xl border border-gray-200 rounded-3xl overflow-hidden" x-data="{ qLevel: 'snapshot' }">
            <div class="px-8 py-6 bg-slate-50 border-b border-gray-200 flex justify-between items-center">
                <div class="flex items-center gap-8">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 uppercase tracking-widest">Question Bank</h2>
                        <p class="text-xs text-slate-500 font-bold mt-1 tracking-tighter italic">Add or modify questions for each level.</p>
                    </div>
                    <!-- Level Toggle -->
                    <div class="bg-gray-200 p-1 rounded-xl flex gap-1">
                        <button @click="qLevel = 'snapshot'" :class="qLevel === 'snapshot' ? 'bg-white shadow-sm' : 'text-gray-500'" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all">Snapshot</button>
                        <button @click="qLevel = 'full'" :class="qLevel === 'full' ? 'bg-white shadow-sm' : 'text-gray-500'" class="px-4 py-1.5 rounded-lg text-[10px] font-black uppercase transition-all">Full Assessment</button>
                    </div>
                </div>
                <button @click="modalMode = 'create'; selectedQuestion = { framework_id: {{ $fw->id }}, level: qLevel }; showQuestionModal = true" class="bg-blue-600 text-white px-6 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg shadow-blue-500/20">Add Question</button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Pillar</th>
                            <th class="px-8 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">Question Wording</th>
                            <th class="px-8 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-8 py-4 text-right text-[10px] font-black text-slate-400 uppercase tracking-widest">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @foreach($fw->questions as $q)
                        <tr x-show="qLevel === '{{ $q->level }}'" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-8 py-5 text-sm font-black text-slate-400">{{ $q->question_code }}</td>
                            <td class="px-8 py-5 text-sm font-bold text-slate-600">{{ $q->pillar->code }}</td>
                            <td class="px-8 py-5 text-sm font-medium text-slate-800 leading-relaxed max-w-xl">{{ $q->question_text }}</td>
                            <td class="px-8 py-5 text-center">
                                @if($q->is_active)
                                    <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded text-[10px] font-black uppercase">Active</span>
                                @else
                                    <span class="bg-gray-100 text-gray-400 px-2.5 py-1 rounded text-[10px] font-black uppercase">Inactive</span>
                                @endif
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-4">
                                    <button @click="selectedQuestion = {{ $q }}; modalMode = 'edit'; showQuestionModal = true" class="text-blue-600 hover:text-blue-800 font-bold text-[10px] uppercase tracking-widest">Edit</button>
                                    <form action="{{ route('admin.frameworks.destroyQuestion', $q->id) }}" method="POST" onsubmit="return confirm('Archive this question?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-[10px] uppercase tracking-widest">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Pillar Modal -->
    <div x-show="showPillarModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-white/20" @click.away="showPillarModal = false">
            <div class="px-8 py-6 bg-slate-50 border-b border-gray-100">
                <h3 class="text-xl font-black text-slate-900 tracking-tight">Edit Pillar: <span x-text="selectedPillar?.code"></span></h3>
            </div>
            <form :action="`/admin/frameworks/pillars/${selectedPillar?.id}`" method="POST" class="p-8 space-y-6">
                @csrf @method('PUT')
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Display Name</label>
                    <input type="text" name="name" x-model="selectedPillar.name" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold">
                </div>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Weight</label>
                        <input type="number" step="0.1" name="weight" x-model="selectedPillar.weight" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Criticality</label>
                        <select name="is_critical" x-model="selectedPillar.is_critical" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold">
                            <option value="1">Critical Area</option>
                            <option value="0">Standard Area</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-4 pt-4">
                    <button type="button" @click="showPillarModal = false" class="flex-1 px-6 py-3 rounded-xl border border-gray-200 font-black text-xs uppercase tracking-widest text-slate-400 hover:bg-gray-50 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Question Modal -->
    <div x-show="showQuestionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-white/20" @click.away="showQuestionModal = false">
            <div class="px-8 py-6 bg-slate-50 border-b border-gray-100">
                <h3 class="text-xl font-black text-slate-900 tracking-tight" x-text="modalMode === 'create' ? 'Add New Question' : 'Edit Question' "></h3>
            </div>
            <form :action="modalMode === 'create' ? '{{ route('admin.frameworks.storeQuestion') }}' : `/admin/frameworks/questions/${selectedQuestion?.id}` " method="POST" class="p-8 space-y-6">
                @csrf
                <template x-if="modalMode === 'edit'">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                
                <input type="hidden" name="framework_id" :value="selectedQuestion?.framework_id">
                <input type="hidden" name="level" :value="selectedQuestion?.level">

                <div class="grid grid-cols-2 gap-6" x-show="modalMode === 'create'">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Pillar ID</label>
                        <select name="pillar_id" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold">
                            <option value="">Select Pillar...</option>
                            @foreach($frameworks as $fw_m)
                                @foreach($fw_m->pillars as $p_m)
                                    <option x-show="tab === '{{ $fw_m->code }}'" value="{{ $p_m->id }}">{{ $p_m->code }} - {{ $p_m->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Question Code (e.g. P1_S5)</label>
                        <input type="text" name="question_code" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold uppercase">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Question Statement</label>
                    <textarea name="question_text" x-model="selectedQuestion.question_text" rows="3" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium leading-relaxed"></textarea>
                </div>

                <div x-show="modalMode === 'edit'">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Active Status</label>
                    <select name="is_active" x-model="selectedQuestion.is_active" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold">
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" @click="showQuestionModal = false" class="flex-1 px-6 py-3 rounded-xl border border-gray-200 font-black text-xs uppercase tracking-widest text-slate-400 hover:bg-gray-50 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20" x-text="modalMode === 'create' ? 'Create Question' : 'Update Question' "></button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
