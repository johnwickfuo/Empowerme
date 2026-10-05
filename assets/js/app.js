document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (toggle && nav) toggle.addEventListener('click', () => nav.classList.toggle('open'));

  document.querySelectorAll('[data-copy]').forEach(btn => btn.addEventListener('click', async () => {
    const value = btn.getAttribute('data-copy') || '';
    try { await navigator.clipboard.writeText(value); btn.textContent = 'Copied'; setTimeout(()=>btn.textContent='Copy code',1500); } catch(e) {}
  }));

  const conditionalFields = document.querySelectorAll('[data-condition]');
  const updateConditional = () => {
    conditionalFields.forEach(el => {
      let condition = null;
      try { condition = JSON.parse(el.dataset.condition || 'null'); } catch(e) {}
      if (!condition || !condition.field) return;
      const nodes = document.querySelectorAll(`[name="custom[${CSS.escape(condition.field)}]"], [name="custom[${CSS.escape(condition.field)}][]"]`);
      let value = '';
      nodes.forEach(n => { if ((n.type === 'radio' || n.type === 'checkbox') ? n.checked : true) value = n.value; });
      let show = condition.operator === 'not_equals' ? value !== condition.value : condition.operator === 'not_empty' ? value.trim() !== '' : condition.operator === 'empty' ? value.trim() === '' : value === condition.value;
      el.hidden = !show;
      el.querySelectorAll('input,select,textarea').forEach(i => { if (i.dataset.wasRequired === '1') i.required = show; });
    });
  };
  conditionalFields.forEach(el => el.querySelectorAll('[required]').forEach(i => i.dataset.wasRequired='1'));
  document.addEventListener('change', updateConditional); document.addEventListener('input', updateConditional); updateConditional();
});
