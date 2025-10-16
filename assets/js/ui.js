(function(){
  if (window.FinUI) return;
  const style = document.createElement('style');
  style.textContent = `
  .finui-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;z-index:9999}
  .finui-modal{background:#fff;border-radius:12px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}
  .finui-header{padding:14px 16px;font-weight:600;border-bottom:1px solid #e2e8f0}
  .finui-body{padding:14px 16px;color:#334155}
  .finui-actions{padding:12px 16px;display:flex;justify-content:flex-end;gap:8px;border-top:1px solid #e2e8f0}
  .finui-btn{padding:8px 12px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer}
  .finui-btn:hover{background:#f8fafc}
  .finui-btn.primary{background:#0f172a;color:#fff;border-color:#0f172a}
  .finui-btn.danger{background:#dc2626;color:#fff;border-color:#dc2626}
  .finui-input{width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:8px}
  .finui-toasts{position:fixed;top:16px;right:16px;display:flex;flex-direction:column;gap:8px;z-index:10000}
  .finui-toast{min-width:220px;max-width:360px;padding:10px 12px;border-radius:10px;color:#0f172a;background:#fff;border:1px solid #e2e8f0;box-shadow:0 10px 30px rgba(0,0,0,.12)}
  .finui-toast.success{border-color:#10b981;background:#ecfdf5;color:#065f46}
  .finui-toast.error{border-color:#ef4444;background:#fef2f2;color:#7f1d1d}
  .finui-toast.warning{border-color:#f59e0b;background:#fffbeb;color:#78350f}
  `;
  document.head.appendChild(style);

  let toasts = document.createElement('div');
  toasts.className = 'finui-toasts';
  function mountToasts(){
    if (!document.body) { return false; }
    if (!toasts.isConnected) document.body.appendChild(toasts);
    return true;
  }
  if (!mountToasts()){
    document.addEventListener('DOMContentLoaded', mountToasts, { once:true });
  }

  function toast(message, type='info', timeout=2800){
  mountToasts();
  const el = document.createElement('div');
    el.className = 'finui-toast' + (type && type!=='info' ? ' ' + type : '');
    el.textContent = message;
    toasts.appendChild(el);
    setTimeout(()=>{ el.style.opacity='0'; el.style.transform='translateY(-6px)'; }, Math.max(10, timeout-300));
    setTimeout(()=>{ el.remove(); }, timeout);
  }

  function confirm({ title='Please confirm', message='', confirmText='Confirm', cancelText='Cancel', danger=false }={}){
    return new Promise(resolve =>{
      const overlay = document.createElement('div'); overlay.className='finui-overlay';
      const box = document.createElement('div'); box.className='finui-modal';
      box.innerHTML = `
        <div class="finui-header">${title}</div>
        <div class="finui-body">${message}</div>
        <div class="finui-actions">
          <button class="finui-btn" data-cancel>${cancelText}</button>
          <button class="finui-btn ${danger?'danger':'primary'}" data-ok>${confirmText}</button>
        </div>`;
      overlay.appendChild(box); document.body.appendChild(overlay);
      const cleanup=()=> overlay.remove();
      box.querySelector('[data-cancel]').onclick = ()=>{ cleanup(); resolve(false); };
      box.querySelector('[data-ok]').onclick = ()=>{ cleanup(); resolve(true); };
      overlay.addEventListener('click', (e)=>{ if (e.target===overlay){ cleanup(); resolve(false); } });
    });
  }

  function prompt({ title='Input', message='', placeholder='', confirmText='OK', cancelText='Cancel' }={}){
    return new Promise(resolve =>{
      const overlay = document.createElement('div'); overlay.className='finui-overlay';
      const box = document.createElement('div'); box.className='finui-modal';
      box.innerHTML = `
        <div class="finui-header">${title}</div>
        <div class="finui-body">
          <div style="margin-bottom:8px;">${message}</div>
          <input class="finui-input" type="text" placeholder="${placeholder}" />
        </div>
        <div class="finui-actions">
          <button class="finui-btn" data-cancel>${cancelText}</button>
          <button class="finui-btn primary" data-ok>${confirmText}</button>
        </div>`;
      overlay.appendChild(box); document.body.appendChild(overlay);
      const input = box.querySelector('input'); input.focus();
      const cleanup=()=> overlay.remove();
      box.querySelector('[data-cancel]').onclick = ()=>{ cleanup(); resolve(null); };
      box.querySelector('[data-ok]').onclick = ()=>{ const v=input.value; cleanup(); resolve(v); };
      overlay.addEventListener('click', (e)=>{ if (e.target===overlay){ cleanup(); resolve(null); } });
    });
  }

  window.FinUI = { toast, confirm, prompt };
})();