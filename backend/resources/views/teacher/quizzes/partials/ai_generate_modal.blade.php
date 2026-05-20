{{-- AI Generate Modal --}}
<div id="aiModal"
     style="display:none; position:fixed; inset:0; z-index:50; align-items:center; justify-content:center; background:rgba(0,0,0,0.6); padding:16px;">

    <div style="background:var(--surface); border:1px solid var(--border-md); border-radius:var(--radius); width:100%; max-width:780px; max-height:90vh; height:90vh; display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,0.5);">

        {{-- Modal Header --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid var(--border); flex-shrink:0;">
            <div>
                <p style="font-size:10px; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; color:var(--accent); margin-bottom:4px;">AI Assistant</p>
                <h2 style="font-size:15px; font-weight:700; color:var(--text); margin-bottom:2px;">✨ Generate Questions with AI</h2>
                <p style="font-size:11.5px; color:var(--text-3);">Powered by Groq AI</p>
            </div>
            <button onclick="closeAiModal()"
                    style="background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); width:30px; height:30px; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-2); font-size:16px; font-weight:700; flex-shrink:0; transition:background 0.15s;"
                    onmouseenter="this.style.background='var(--surface-2)'"
                    onmouseleave="this.style.background='var(--surface-3)'">&times;</button>
        </div>

        {{-- Modal Body --}}
        <div style="overflow-y:auto; flex:1; padding:22px 24px;">

            {{-- STEP 1: Input Form --}}
            <div id="aiStep1">

                {{-- Topic --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">Topic / Keyword</label>
                    <input type="text" id="aiTopic"
                           placeholder="e.g. Photosynthesis, World War 2..."
                           style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                           onfocus="this.style.borderColor='var(--accent)'"
                           onblur="this.style.borderColor='var(--border-md)'">
                </div>

                {{-- Passage --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">
                        Passage / Text
                        <span style="font-size:10.5px; font-weight:400; color:var(--text-3); text-transform:none; letter-spacing:0;">(optional)</span>
                    </label>
                    <textarea id="aiPassage" rows="4"
                              placeholder="Paste a text or reading passage here..."
                              style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; resize:vertical; box-sizing:border-box; transition:border-color 0.15s;"
                              onfocus="this.style.borderColor='var(--accent)'"
                              onblur="this.style.borderColor='var(--border-md)'"></textarea>
                </div>

                {{-- Count + Difficulty --}}
                <div class="ai-two-col" style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">Number of Questions</label>
                        <input type="number" id="aiNumQuestions" value="15" min="1" max="30"
                            style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--mono); outline:none; box-sizing:border-box; transition:border-color 0.15s;"
                            onfocus="this.style.borderColor='var(--accent)'"
                            onblur="this.style.borderColor='var(--border-md)'"
                            onkeydown="if(['e','E','+','-','.'].includes(event.key)) event.preventDefault()">
                    </div>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:7px;">Difficulty</label>
                        <select id="aiDifficulty"
                                style="width:100%; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:9px 13px; font-size:13px; color:var(--text); font-family:var(--font); outline:none; box-sizing:border-box; cursor:pointer; transition:border-color 0.15s;"
                                onfocus="this.style.borderColor='var(--accent)'"
                                onblur="this.style.borderColor='var(--border-md)'">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                </div>

                {{-- Question Types --}}
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:var(--text-2); letter-spacing:0.05em; text-transform:uppercase; margin-bottom:10px;">Question Types</label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        @foreach([
                            'multiple_choice' => 'Multiple Choice',
                            'true_false'      => 'True / False',
                            'identification'  => 'Identification',
                            'matching'        => 'Matching',
                        ] as $value => $label)
                        <label style="display:flex; align-items:center; gap:7px; cursor:pointer; background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:7px 12px; font-size:12px; font-weight:600; color:var(--text-2); transition:border-color 0.15s;"
                               onmouseenter="this.style.borderColor='var(--accent)'"
                               onmouseleave="this.style.borderColor='var(--border-md)'">
                            <input type="checkbox" name="aiQuestionTypes" value="{{ $value }}" checked
                                   style="accent-color:var(--accent); width:13px; height:13px; cursor:pointer;">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Error --}}
                <div id="aiError" style="display:none;" class="attention-rose">
                    <span id="aiErrorText" style="font-size:12px; color:var(--danger);"></span>
                </div>

            </div>

            {{-- STEP 2: Preview --}}
            <div id="aiStep2" style="display:none;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px;">
                    <p style="font-size:12px; font-weight:600; color:var(--text-2);">Review and edit questions before saving.</p>
                    <button onclick="backToStep1()"
                            style="background:none; border:none; font-size:12px; font-weight:700; color:var(--accent); cursor:pointer; padding:0; font-family:var(--font);">
                        ← Regenerate
                    </button>
                </div>
                <div id="aiPreviewList" style="display:flex; flex-direction:column; gap:12px;"></div>
            </div>

            {{-- Loading --}}
            <div id="aiLoading" style="display:none; flex-direction:column; align-items:center; justify-content:center; padding:64px 24px; gap:16px;">
                <div style="width:40px; height:40px; border-radius:50%; border:3px solid var(--surface-3); border-top-color:var(--accent); animation:ai-spin 0.7s linear infinite;"></div>
                <p style="font-size:13px; color:var(--text-2); font-weight:500;">AI is generating your questions…</p>
            </div>

        </div>

        {{-- Modal Footer --}}
        <div style="display:flex; align-items:center; justify-content:flex-end; gap:8px; padding:16px 24px; border-top:1px solid var(--border); flex-shrink:0;">
            <button onclick="closeAiModal()" class="btn btn-ghost btn-sm">Cancel</button>
            <button id="aiBtnGenerate" onclick="generateQuestions()" class="btn btn-primary btn-sm" style="display:inline-flex; align-items:center; gap:5px;">
                <span>✨</span> Generate
            </button>
            <button id="aiBtnSave" onclick="saveQuestions()" class="btn btn-primary btn-sm" style="display:none; align-items:center; gap:5px;">
                <span>💾</span> Save to Quiz
            </button>
        </div>

    </div>
</div>

<style>
@keyframes ai-spin { to { transform: rotate(360deg); } }
@media (max-width: 600px) {
    .ai-two-col { grid-template-columns: 1fr !important; }
}
</style>

<script>
const QUIZ_ID    = {{ $quiz->id }};
const CSRF_TOKEN = '{{ csrf_token() }}';
let generatedQuestions = [];

function openAiModal()  { const m = document.getElementById('aiModal'); m.style.display = 'flex'; }
function closeAiModal() { const m = document.getElementById('aiModal'); m.style.display = 'none'; resetAiModal(); }

function resetAiModal() {
    showStep(1);
    setError(null);
    document.getElementById('aiPreviewList').innerHTML = '';
    generatedQuestions = [];
}

function backToStep1() { showStep(1); }

function setError(msg) {
    const box  = document.getElementById('aiError');
    const text = document.getElementById('aiErrorText');
    if (msg) { text.textContent = msg; box.style.display = 'block'; }
    else      { box.style.display = 'none'; text.textContent = ''; }
}

function showStep(step) {
    document.getElementById('aiStep1').style.display   = step === 1 ? 'block' : 'none';
    document.getElementById('aiStep2').style.display   = step === 2 ? 'block' : 'none';
    document.getElementById('aiLoading').style.display = 'none';
    document.getElementById('aiBtnGenerate').style.display = step === 1 ? 'inline-flex' : 'none';
    document.getElementById('aiBtnSave').style.display     = step === 2 ? 'inline-flex' : 'none';
}

async function generateQuestions() {
    const topic   = document.getElementById('aiTopic').value.trim();
    const passage = document.getElementById('aiPassage').value.trim();
    const num     = document.getElementById('aiNumQuestions').value;
    const diff    = document.getElementById('aiDifficulty').value;
    const types  = [...document.querySelectorAll('input[name="aiQuestionTypes"]:checked')].map(c => c.value);
    const numVal = parseInt(num);

    setError(null);

    if (!topic && !passage) { setError('Please provide a topic or a passage.'); return; }
    if (types.length === 0)  { setError('Please select at least one question type.'); return; }
    if (!num || isNaN(numVal) || numVal < 1 || numVal > 30) {setError('Number of questions must be a whole number between 1 and 30.');return;}

    document.getElementById('aiStep1').style.display   = 'none';
    document.getElementById('aiLoading').style.display = 'flex';
    document.getElementById('aiBtnGenerate').style.display = 'none';

    try {
        const res = await fetch('{{ route("teacher.quizzes.ai.generate") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            // body: JSON.stringify({ topic, passage, num_questions: parseInt(num), difficulty: diff, question_types: types }),
            body: JSON.stringify({ topic, passage, num_questions: numVal, difficulty: diff, question_types: types }),
            credentials: 'same-origin',
        });

        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'AI generation failed.');

        generatedQuestions = data.questions;
        renderPreview(generatedQuestions);
        showStep(2);

    } catch (err) {
        document.getElementById('aiLoading').style.display = 'none';
        document.getElementById('aiStep1').style.display   = 'block';
        document.getElementById('aiBtnGenerate').style.display = 'inline-flex';
        setError(err.message);
    }
}

/* ── Shared input style string for dynamically created inputs ── */
const iStyle  = `background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:7px 11px; font-size:12px; color:var(--text); font-family:var(--font); outline:none; transition:border-color 0.15s;`;
const iFocus  = `this.style.borderColor='var(--accent)'`;
const iBlur   = `this.style.borderColor='var(--border-md)'`;

/* ── Badge colours mapped to design-system badge classes ── */
const typeBadgeClass = {
    multiple_choice: 'badge badge-sky',
    true_false:      'badge badge-amber',
    identification:  'badge badge-green',
    matching:        'badge badge-slate',
};
const typeLabel = {
    multiple_choice: 'Multiple Choice',
    true_false:      'True / False',
    identification:  'Identification',
    matching:        'Matching',
};

function renderPreview(questions) {
    const container = document.getElementById('aiPreviewList');
    container.innerHTML = '';

    questions.forEach((q, idx) => {
        let answersHtml = '';

        if (q.type === 'multiple_choice') {
            answersHtml = q.options.map((opt, oi) => `
                <div style="display:flex; align-items:center; gap:8px; margin-top:6px;">
                    <input type="radio" name="mc_correct_${idx}" value="${oi}" ${opt.is_correct ? 'checked' : ''}
                           onchange="updateCorrectOption(${idx},${oi})"
                           style="accent-color:var(--accent); width:13px; height:13px; cursor:pointer; flex-shrink:0;">
                    <input type="text" value="${escHtml(opt.option_text)}"
                           oninput="updateOption(${idx},${oi},this.value)"
                           style="flex:1; ${iStyle}"
                           onfocus="${iFocus}" onblur="${iBlur}">
                </div>`).join('');

        } else if (q.type === 'true_false') {
            answersHtml = `
                <div style="display:flex; gap:10px; margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer; font-size:12px; font-weight:600; color:var(--text); background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 14px;">
                        <input type="radio" name="tf_${idx}" value="true" ${q.correct_answer === true ? 'checked' : ''}
                               onchange="updateTrueFalse(${idx},true)"
                               style="accent-color:var(--accent); width:13px; height:13px; cursor:pointer;"> True
                    </label>
                    <label style="display:flex; align-items:center; gap:7px; cursor:pointer; font-size:12px; font-weight:600; color:var(--text); background:var(--surface-3); border:1px solid var(--border-md); border-radius:var(--radius-sm); padding:6px 14px;">
                        <input type="radio" name="tf_${idx}" value="false" ${q.correct_answer === false ? 'checked' : ''}
                               onchange="updateTrueFalse(${idx},false)"
                               style="accent-color:var(--accent); width:13px; height:13px; cursor:pointer;"> False
                    </label>
                </div>`;

        } else if (q.type === 'identification') {
            answersHtml = `
                <div style="margin-top:8px;">
                    <p style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3); margin-bottom:5px;">Answer</p>
                    <input type="text" value="${escHtml(q.answer)}"
                           oninput="updateIdentification(${idx},this.value)"
                           style="width:100%; box-sizing:border-box; ${iStyle}"
                           onfocus="${iFocus}" onblur="${iBlur}">
                </div>`;

        } else if (q.type === 'matching') {
            const colHeader = `
                <div style="display:grid; grid-template-columns:1fr 24px 1fr; gap:6px; margin-bottom:4px;">
                    <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">Premise</span>
                    <span></span>
                    <span style="font-size:10px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:var(--text-3);">Match</span>
                </div>`;
            answersHtml = colHeader + q.pairs.map((pair, pi) => `
                <div style="display:grid; grid-template-columns:1fr 24px 1fr; gap:6px; align-items:center; margin-top:6px;">
                    <input type="text" value="${escHtml(pair.left)}"
                           oninput="updatePair(${idx},${pi},'left',this.value)"
                           style="${iStyle}" onfocus="${iFocus}" onblur="${iBlur}">
                    <span style="text-align:center; font-size:12px; color:var(--text-3);">→</span>
                    <input type="text" value="${escHtml(pair.right)}"
                           oninput="updatePair(${idx},${pi},'right',this.value)"
                           style="${iStyle}" onfocus="${iFocus}" onblur="${iBlur}">
                </div>`).join('');
        }

        const card = document.createElement('div');
        card.id = `q-card-${idx}`;
        card.style.cssText = `background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius); padding:16px 18px;`;
        card.innerHTML = `
            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:10px;">
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span class="chip num" style="color:var(--text-3); font-size:10.5px;">Q${idx + 1}</span>
                    <span class="${typeBadgeClass[q.type] || 'badge badge-slate'}" style="font-size:10px;">${typeLabel[q.type] || q.type}</span>
                </div>
                <button onclick="removeQuestion(${idx})"
                        style="background:rgba(248,113,113,0.08); border:1px solid rgba(248,113,113,0.18); border-radius:var(--radius-sm); font-size:11px; font-weight:700; color:var(--danger); padding:4px 10px; cursor:pointer; flex-shrink:0; font-family:var(--font);">
                    Remove
                </button>
            </div>
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                <input type="text" value="${escHtml(q.question_text)}"
                       oninput="updateQuestionText(${idx},this.value)"
                       style="flex:1; font-weight:600; ${iStyle}"
                       onfocus="${iFocus}" onblur="${iBlur}">
                <div style="display:flex; align-items:center; gap:5px; flex-shrink:0;">
                    <input type="number" value="${q.points}" min="1"
                           oninput="updatePoints(${idx},this.value)"
                           style="width:58px; text-align:center; font-family:var(--mono); ${iStyle}"
                           onfocus="${iFocus}" onblur="${iBlur}">
                    <span style="font-size:11px; color:var(--text-3);">pts</span>
                </div>
            </div>
            <div style="padding-left:2px;">${answersHtml}</div>`;
        container.appendChild(card);
    });
}

function escHtml(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function updateQuestionText(idx, val)       { generatedQuestions[idx].question_text = val; }
function updatePoints(idx, val)             { generatedQuestions[idx].points = parseInt(val) || 1; }
function updateTrueFalse(idx, val)          { generatedQuestions[idx].correct_answer = val; }
function updateIdentification(idx, val)     { generatedQuestions[idx].answer = val; }
function updateOption(idx, oi, val)         { generatedQuestions[idx].options[oi].option_text = val; }
function updatePair(idx, pi, side, val)     { generatedQuestions[idx].pairs[pi][side] = val; }
function updateCorrectOption(idx, oi)       { generatedQuestions[idx].options.forEach((o,i) => o.is_correct = i === oi); }
function removeQuestion(idx) {
    generatedQuestions.splice(idx, 1);
    if (generatedQuestions.length === 0) { backToStep1(); return; }
    renderPreview(generatedQuestions);
}

async function saveQuestions() {
    const btn = document.getElementById('aiBtnSave');
    setError(null);
    btn.disabled = true;
    btn.textContent = 'Saving…';

    try {
        const res = await fetch('{{ route("teacher.quizzes.ai.save", ["quizId" => $quiz->id]) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ questions: generatedQuestions }),
            credentials: 'same-origin',
        });

        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to save questions.');

        closeAiModal();
        window.location.reload();

    } catch (err) {
        setError(err.message);
        btn.disabled = false;
        btn.innerHTML = '<span>💾</span> Save to Quiz';
    }
}
</script>
