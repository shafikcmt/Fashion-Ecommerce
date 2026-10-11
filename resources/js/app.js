
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
