
const menuToggle = document.querySelector('[data-menu-toggle]');
const navLinks = document.querySelector('.nav-links');
if(menuToggle && navLinks){menuToggle.addEventListener('click',()=>navLinks.classList.toggle('open'));}

const current = window.location.pathname.split('/').pop() || 'index.html';
document.querySelectorAll('a[data-nav]').forEach(link=>{
  const href = link.getAttribute('href');
  if(href===current) link.classList.add('active');
});

document.querySelectorAll('[data-service]').forEach(link=>{
  link.addEventListener('click',()=>{
    const service = link.getAttribute('data-service');
    if(service) localStorage.setItem('selectedService', service);
  });
});

const serviceField = document.querySelector('#selectedService');
if(serviceField){
  const params = new URLSearchParams(window.location.search);
  const value = params.get('service') || localStorage.getItem('selectedService');
  if(value) serviceField.value = value;
}

document.querySelectorAll('form[data-demo-form]').forEach(form=>{
  form.addEventListener('submit',e=>{
    e.preventDefault();
    const result = form.querySelector('.result-box');
    const notice = form.querySelector('.notice');
    if(result) result.classList.add('show');
    if(notice) notice.classList.add('show');
    window.scrollTo({top: form.offsetTop - 80, behavior:'smooth'});
  });
});

document.querySelectorAll('[data-assessment]').forEach(form=>{
  form.addEventListener('submit', e=>{
    e.preventDefault();
    const type = form.getAttribute('data-assessment');
    const scores = Array.from(form.querySelectorAll('input[type="range"]')).map(i=>Number(i.value));
    const avg = scores.reduce((a,b)=>a+b,0)/scores.length;
    let rag='Green', cls='rag-green', msg='Broadly controlled, but a deeper review may still reveal hidden weaknesses.';
    if(avg<2.5){rag='Red';cls='rag-red';msg = type==='phi' ? 'Material delivery risk detected. An executive PHI review is strongly recommended.' : 'Material service and operational risk detected. A full ITSM health review is strongly recommended.';}
    else if(avg<3.8){rag='Amber';cls='rag-amber';msg = type==='phi' ? 'Partial control is visible, with exposed delivery risk across some domains.' : 'The service is partially controlled but remains exposed to operational risk.';}
    const box = form.querySelector('.result-box');
    if(box){
      box.innerHTML = `<div class="badge">${type==='phi'?'Programme Health Check':'Service Health Check'} result</div>
      <div class="result-score ${cls}">${avg.toFixed(1)}/5</div>
      <h3 class="${cls}">${rag}</h3>
      <p>${msg}</p>
      <div class="hero-actions"><a class="btn primary" href="quote-request.html">Request a quote</a><a class="btn secondary" href="admin-dashboard.html">View internal portal preview</a></div>`;
      box.classList.add('show');
    }
  });
});

const wizard = document.querySelector('[data-rapid-wizard]');
if(wizard){
  const steps = Array.from(wizard.querySelectorAll('.wizard-step'));
  const stepItems = Array.from(document.querySelectorAll('[data-step-item]'));
  const nextBtn = wizard.querySelector('[data-next-step]');
  const prevBtn = wizard.querySelector('[data-prev-step]');
  let currentStep = 1;
  function validateCurrentStep(){
    const current = wizard.querySelector(`.wizard-step[data-step="${currentStep}"]`);
    const fields = Array.from(current.querySelectorAll('input,select,textarea'));
    for(const field of fields){ if(!field.checkValidity()){ field.reportValidity(); return false; } }
    return true;
  }
  function renderStep(){
    steps.forEach(step=>step.classList.toggle('active', Number(step.dataset.step)===currentStep));
    stepItems.forEach(item=>item.classList.toggle('active', Number(item.dataset.stepItem)===currentStep));
    if(prevBtn) prevBtn.style.visibility = currentStep===1?'hidden':'visible';
    if(nextBtn) nextBtn.style.display = currentStep===steps.length?'none':'inline-flex';
  }
  if(nextBtn){ nextBtn.addEventListener('click',()=>{ if(!validateCurrentStep()) return; if(currentStep<steps.length){ currentStep+=1; renderStep(); wizard.scrollIntoView({behavior:'smooth', block:'start'}); } }); }
  if(prevBtn){ prevBtn.addEventListener('click',()=>{ if(currentStep>1){ currentStep-=1; renderStep(); wizard.scrollIntoView({behavior:'smooth', block:'start'}); } }); }
  renderStep();
}
