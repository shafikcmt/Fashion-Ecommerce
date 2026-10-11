
document.querySelectorAll('[data-print]').forEach(button=>button.addEventListener('click',()=>window.print()));
document.querySelectorAll('[data-once-submit]').forEach(button=>button.form.addEventListener('submit',()=>{ button.disabled=true; button.textContent=document.documentElement.lang==='bn'?'অর্ডার পাঠানো হচ্ছে…':'Placing your order…'; }));
document.querySelectorAll('[data-saved-address]').forEach(select=>select.addEventListener('change',()=>{
 const data=select.selectedOptions[0].dataset;
 for(const name of ['name','phone','address','district','postcode']) { const field=select.form.elements.namedItem(name); if(field && data[name]!==undefined) field.value=data[name]; }
}));

document.querySelectorAll('[data-hero-slider]').forEach(slider=>{
 const slides=[...slider.querySelectorAll('[data-slide]')];let active=0;
 const show=index=>{ active=(index+slides.length)%slides.length;slides.forEach((slide,i)=>slide.hidden=i!==active);const label=slider.querySelector('[data-slide-label]');if(label)label.textContent=(active+1)+' / '+slides.length; };
 slider.querySelector('[data-slide-prev]')?.addEventListener('click',()=>show(active-1));
 slider.querySelector('[data-slide-next]')?.addEventListener('click',()=>show(active+1));
 if(slides.length)show(0);
});


document.querySelectorAll('[data-responsive-filters]').forEach(panel => {
 const desktop = window.matchMedia('(min-width: 961px)');
 const update = () => { panel.open = desktop.matches; };
 desktop.addEventListener('change', update);
 update();
});

document.querySelectorAll('.mobile-nav').forEach(menu => {
 menu.addEventListener('keydown', event => {
  if (event.key === 'Escape') { menu.open = false; menu.querySelector('summary').focus(); }
 });
 document.addEventListener('click', event => { if (!menu.contains(event.target)) menu.open = false; });
});

document.querySelectorAll('.gallery').forEach(gallery => {
 gallery.addEventListener('keydown', event => {
  if (!['ArrowLeft', 'ArrowRight'].includes(event.key) || gallery.scrollWidth <= gallery.clientWidth) return;
  event.preventDefault();
  gallery.scrollBy({ left: (event.key === 'ArrowRight' ? 1 : -1) * (gallery.clientWidth + 20), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
 });
});
