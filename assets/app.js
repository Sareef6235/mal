const $ = (s) => document.querySelector(s);
let muted = false;
const sounds = {
  click: new Audio('data:audio/wav;base64,UklGRlQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YTAAAAA='),
  success: new Audio('data:audio/wav;base64,UklGRlQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YTAAAAA='),
  close: new Audio('data:audio/wav;base64,UklGRlQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YTAAAAA=')
};
const play = (k) => { if (!muted) sounds[k]?.play().catch(() => {}); };

document.addEventListener('click', (e) => { if (e.target.closest('.ripple')) play('click'); });

window.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => { $('#skeletonCard')?.remove(); $('#loginWrap')?.classList.remove('hidden'); }, 500);

  $('#muteToggle')?.addEventListener('click', () => {
    muted = !muted; $('#muteToggle').textContent = muted ? '🔇' : '🔊';
  });

  $('#togglePwd')?.addEventListener('click', () => {
    const pwd = $('#password'); if (!pwd) return;
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    $('#togglePwd').textContent = pwd.type === 'password' ? '👁' : '🙈';
  });

  if (window.lottie && $('#lockLottie')) {
    lottie.loadAnimation({container:$('#lockLottie'),renderer:'svg',loop:true,autoplay:true,path:'https://assets5.lottiefiles.com/packages/lf20_yub9jjsz.json'});
  }

  if (window.APP_LOGIN_STATE?.success) {
    play('success');
    launchConfetti();
    const modal = $('#successModal');
    modal?.classList.add('show');
    modal?.setAttribute('aria-hidden','false');
    const go = () => window.location.href = window.APP_LOGIN_STATE.redirectUrl;
    $('#closeModal')?.addEventListener('click', () => { play('close'); go(); });
    setTimeout(() => { play('close'); go(); }, 5000);
  }

  document.querySelectorAll('.counter').forEach((el) => {
    const target = +el.dataset.target; let val = 0;
    const step = Math.max(1, Math.round(target / 60));
    const tick = () => { val += step; if (val >= target) val = target; el.textContent = val; if (val < target) requestAnimationFrame(tick); };
    requestAnimationFrame(tick);
  });

  $('#collapseBtn')?.addEventListener('click', () => $('#sidebar')?.classList.toggle('collapsed'));
  $('#drawerBtn')?.addEventListener('click', () => $('#sidebar')?.classList.toggle('open'));
});

function launchConfetti(){
  const c = document.getElementById('confettiCanvas'); if(!c) return;
  const ctx = c.getContext('2d'); c.width = innerWidth; c.height = innerHeight;
  const p = Array.from({length:80},()=>({x:Math.random()*c.width,y:Math.random()*-c.height,r:3+Math.random()*5,s:1+Math.random()*3}));
  let t=0; (function anim(){ ctx.clearRect(0,0,c.width,c.height); p.forEach(i=>{i.y+=i.s;i.x+=Math.sin(i.y*0.03);ctx.fillStyle=`hsl(${(i.y+i.x)%360} 90% 60%)`;ctx.fillRect(i.x,i.y,i.r,i.r)}); if(t++<240) requestAnimationFrame(anim);})();
}
