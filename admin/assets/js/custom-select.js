// Custom Select - Sage - auto enhance all <select> to fix icon & blue dropdown
(function(){
  // Icon mapping by select name/id
  function getIcon(select){
    const name = (select.name || select.id || '').toLowerCase();
    if(name.includes('kota')) return 'fa-location-dot';
    if(name.includes('metode')) return 'fa-credit-card';
    if(name.includes('hotel') || name.includes('id_hotel')) return 'fa-hotel';
    if(name.includes('status_pembayaran') || name.includes('status') ) return 'fa-tag';
    if(name.includes('tipe')) return 'fa-bed';
    return 'fa-chevron-down'; // fallback
  }
  function getOptionIcon(value, text){
    const v = (value||'').toLowerCase();
    const t = (text||'').toLowerCase();
    if(!value) return 'fa-earth-asia';
    if(v.includes('transfer') || t.includes('transfer')) return 'fa-building-columns';
    if(v.includes('e-wallet') || t.includes('e-wallet') || t.includes('ovo') || t.includes('gopay')) return 'fa-wallet';
    if(v.includes('kartu') || t.includes('kartu')) return 'fa-credit-card';
    if(v.includes('tunai') || t.includes('tunai')) return 'fa-money-bill-wave';
    if(v.includes('pending')) return 'fa-clock';
    if(v.includes('success') || v.includes('confirmed')) return 'fa-circle-check';
    if(v.includes('failed') || v.includes('cancelled')) return 'fa-circle-xmark';
    if(v.includes('superior')) return 'fa-bed';
    if(v.includes('deluxe')) return 'fa-star';
    if(v.includes('suite')) return 'fa-crown';
    if(t.includes('bandung') || t.includes('soreang')) return 'fa-city';
    return 'fa-circle';
  }

  function enhanceSelect(select){
    if(select.dataset.csEnhanced === '1') return;
    if(select.closest('.custom-dropdown') || select.closest('.custom-select')) return; // sudah custom manual (hotels.php)
    // skip multiple
    if(select.multiple) return;
    // Determine variant by context - pill untuk filter bar, table untuk admin, form untuk form-group
    let variant = 'form';
    if(select.closest('.filter-bar')){
      variant = 'pill';
    } else if(select.closest('.table-toolbar')){
      variant = 'table';
    } else if(select.closest('.form-group')){
      variant = 'form';
    }
    if(select.id==='kotaSelect') variant='pill';

    const wrapper = document.createElement('div');
    wrapper.className = 'custom-select custom-select--' + variant;
    // Insert wrapper before select
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    select.classList.add('cs-hidden');
    select.dataset.csEnhanced = '1';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'custom-select-trigger';
    trigger.setAttribute('aria-haspopup','listbox');
    trigger.setAttribute('aria-expanded','false');

    const left = document.createElement('span');
    left.className = 'trigger-left';
    const icon = document.createElement('i');
    icon.className = 'fa-solid ' + getIcon(select);
    const text = document.createElement('span');
    // initial text
    const selectedOpt = select.options[select.selectedIndex];
    text.textContent = selectedOpt ? selectedOpt.textContent.trim() : (select.options[0]?.textContent.trim() || 'Pilih');
    if(!select.value) text.classList.add('placeholder');
    left.appendChild(icon);
    left.appendChild(text);

    const arrow = document.createElement('i');
    arrow.className = 'fa-solid fa-chevron-down custom-select-arrow';

    trigger.appendChild(left);
    trigger.appendChild(arrow);

    const menu = document.createElement('div');
    menu.className = 'custom-select-menu';
    menu.setAttribute('role','listbox');

    Array.from(select.options).forEach(opt => {
      const div = document.createElement('div');
      div.className = 'custom-option' + (opt.value === select.value ? ' active' : '');
      div.setAttribute('role','option');
      div.dataset.value = opt.value;
      const oIcon = document.createElement('i');
      oIcon.className = 'fa-solid ' + getOptionIcon(opt.value, opt.textContent);
      const oText = document.createElement('span');
      oText.textContent = opt.textContent.trim();
      div.appendChild(oIcon);
      div.appendChild(oText);
      if(opt.disabled) div.style.opacity = '.5';
      div.addEventListener('click', () => {
        select.value = opt.value;
        select.dispatchEvent(new Event('change', {bubbles:true}));
        text.textContent = opt.textContent.trim();
        text.classList.toggle('placeholder', !opt.value);
        menu.querySelectorAll('.custom-option').forEach(el=>el.classList.remove('active'));
        div.classList.add('active');
        close();
      });
      menu.appendChild(div);
    });

    wrapper.appendChild(trigger);
    wrapper.appendChild(menu);

    function open(){ wrapper.classList.add('open'); trigger.setAttribute('aria-expanded','true'); }
    function close(){ wrapper.classList.remove('open'); trigger.setAttribute('aria-expanded','false'); }
    function toggle(){ wrapper.classList.contains('open') ? close() : open(); }

    trigger.addEventListener('click', (e)=>{ e.stopPropagation(); toggle(); });
    // also allow click on left icon? trigger already handles

    // Close on outside click
    document.addEventListener('click', (e)=>{ if(!wrapper.contains(e.target)) close(); });
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });

    // Sync if select changed programmatically
    select.addEventListener('change', ()=>{
      const opt = select.options[select.selectedIndex];
      if(opt){
        text.textContent = opt.textContent.trim();
        text.classList.toggle('placeholder', !opt.value);
        menu.querySelectorAll('.custom-option').forEach(el=>{
          el.classList.toggle('active', el.dataset.value === select.value);
        });
      }
    });
  }

  function init(){
    document.querySelectorAll('select').forEach(enhanceSelect);
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for manual re-init after ajax
  window.initCustomSelects = init;
})();
